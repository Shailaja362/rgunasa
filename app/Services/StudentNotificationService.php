<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentNotificationService
{
    /**
     * Dispatch a notification by type. `$params` keys depend on `$type`:
     *   - 'event_published':   ['event' => Event]
     *   - 'attendance_marked': ['student' => Student, 'event' => Event]
     *   - 'grade_assigned':    ['student' => Student, 'event' => Event, 'grade' => string]
     *
     * For 'event_published', call this only when an event transitions from
     * unpublished to published — student-facing queries filter on publish = 1,
     * so notifying earlier would reference an event students can't see yet.
     */
    public function notify(string $type, array $params): void
    {
        switch ($type) {
            case 'event_published':
                $event = $params['event'];
                $students = $this->studentsForEvent($event);
                $title = 'New Event: ' . $event->title;
                $description = "A new event \"{$event->title}\" has been scheduled on {$this->eventDate($event)}. Check it out!";
                break;

            case 'attendance_marked':
                $students = collect([$params['student']]);
                $title = 'Attendance Marked';
                $description = "Your attendance for \"{$params['event']->title}\" has been recorded.";
                break;

            case 'grade_assigned':
                $students = collect([$params['student']]);
                $title = 'Grade Assigned';
                $description = "Your grade for \"{$params['event']->title}\" has been updated to \"" . strtoupper($params['grade']) . "\".";
                break;

            default:
                return;
        }

        $this->send($students, $title, $description);
    }

    protected function studentsForEvent(Event $event): Collection
    {
        $students = collect();

        foreach ($event->schedules as $schedule) {
            $query = Student::query();

            if ($schedule->isCommonFirstYearSchedule()) {
                $query->whereIn('semester', ['1', '2']);
                if (!empty($schedule->batch)) {
                    $query->where('batch', $schedule->batch);
                }
            } else {
                $query->where('programme_id', $schedule->programme_id)
                    ->where('section', $schedule->section)
                    ->where('semester', $schedule->semester);
                if (!empty($schedule->batch)) {
                    $query->where('batch', $schedule->batch);
                }
            }

            $students = $students->merge($query->get());
        }

        return $students->unique('id');
    }

    protected function send(Collection $students, string $title, string $description): void
    {
        foreach ($students as $student) {
            Notification::create([
                'student_id'  => $student->id,
                'title'       => $title,
                'description' => $description,
                'status'      => 0,
                'role'        => 2,
            ]);

            $this->pushFcm($student, $title, $description);
        }
    }

    protected function pushFcm(Student $student, string $title, string $body): void
    {
        if (empty($student->device_token)) {
            // Student hasn't opened the app / allowed notifications yet.
            return;
        }

        if (!class_exists(\Kreait\Firebase\Factory::class)) {
            Log::error('FCM push skipped: kreait/firebase-php is not installed (run composer install).');
            return;
        }

        try {
            // Data-only message: the app's own FCM handler renders the
            // notification. Sending a `notification` block here as well makes
            // the OS auto-render a second, generic copy alongside the app's
            // real one (the classic Android FCM "double notification" bug).
            // Keys must match what firebaseMessagingBackgroundHandler() reads
            // on the Flutter side (message.data['custom_title'] / ['custom_body']).
            $message = \Kreait\Firebase\Messaging\CloudMessage::new()
                ->withToken($student->device_token)
                ->withData([
                    'custom_title' => $title,
                    'custom_body'  => $body,
                ]);

            app('firebase')->send($message);
        } catch (\Throwable $e) {
            Log::warning('FCM push notification failed', [
                'student_id' => $student->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function eventDate(Event $event): string
    {
        $firstSchedule = $event->schedules->first();
        return $firstSchedule
            ? \Carbon\Carbon::parse($firstSchedule->event_date)->format('F j, Y')
            : '';
    }
}
