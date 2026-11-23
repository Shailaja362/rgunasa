<?php

namespace App\Listeners;

use App\Events\SendPushNotification;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;

class SendPushNotificationListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SendPushNotification $event): void
    {
        $receiver = $event->receiver;
        switch ($event->type) {
            case 1:
                $this->notifyUser(
                    receiver: $receiver,
                    title: 'Project Accepted',
                    description: 'The influencer accepted the project.',
                    category: 'success'
                );
                break;

//            case 2:
//                $influencer = $event->data['influencer'] ?? null;
//                $code = $event->data['project_code'] ?? null;
//                $name = $influencer?->name ?? 'An Influencer';
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Influencer {$name} Task Completed",
//                    description: "The influencer uploaded the link this project CP00 {$code}",
//                    category: 'success'
//                );
//                break;
//
//            case 3:
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Banner Create",
//                    description: 'Your banner has been created successfully.',
//                    category: 'success',
//                    type: 'influencer'
//                );
//                break;
//            case 4:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Waiting for Accept",
//                    description: "Project assigned by you, Accepted this Project CP00 {$code}.",
//                    category: 'success',
//                    type: 'influencer',
//                    redirect_type: 'client-pending'
//                );
//                break;
//            case 5:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Rework",
//                    description: "Rework this Project CP00 {$code}.",
//                    category: 'warning',
//                    type: 'influencer',
//                    redirect_type: 'client-rework'
//                );
//                break;
//            case 6:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Completed the Project",
//                    description: "Verify and completed the project CP00 {$code}.",
//                    category: 'success',
//                );
//                break;
//            case 7:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Cancel the Project",
//                    description: "Cancel this project CP00 {$code}.",
//                    category: 'alert',
//                );
//                break;
//            case 8:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Promote Project Created",
//                    description: "Promote Project Assigned. The project Id is PR-SUB-    {$code}.",
//                    category: 'success',
//                    type: 'influencer',
//                    redirect_type: 'promote-pending'
//                );
//                break;
//            case 9:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: 'Project Accepted',
//                    description: "The influencer accepted the project. The project Id is PR-SUB-{$code}.",
//                    category: 'success',
//                    type: 'admin'
//                );
//                break;
//            case 10:
//                $influencer_name = $event->data['inf_name'] ?? 'An Influencer';
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Influencer {$influencer_name} Task Completed",
//                    description: "The influencer uploaded the link this project PR-SUB-{$code}",
//                    category: 'success',
//                    type: 'admin'
//                );
//                break;
//            case 11:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Rework",
//                    description: "Rework this Project PR-SUB-{$code}.",
//                    category: 'warning',
//                    type: 'influencer',
//                    redirect_type: 'promote-rework'
//                );
//                break;
//            case 12:
//                $code = $event->data['project_code'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Cancel the Project",
//                    description: "Cancel this project PR-SUB-{$code}.",
//                    category: 'alert',
//                    type: 'influencer'
//                );
//                break;
//            case 13:
//                $user_name = $event->data['user_name'] ?? null;
//                $influencer_name = $event->data['influencer_name'] ?? null;
//                $this->notifyUser(
//                    receiver: $receiver,
//                    title: "Request Influencer",
//                    description: "Client-{$user_name} as requested by the influencer-{$influencer_name}.",
//                    type: 'admin'
//                );
//                break;
        }
    }

    private function notifyUser($receiver, string $title, string $description, string $category = 'info', string $type = 'client', string $redirect_type = 'general')
    {
        DB::beginTransaction();

        try {

            // Send Push Notification
            if (!empty($receiver->device_token)) {

                try {

                    firebase()
                        ->toToken($receiver->device_token)
                        ->notification(
                            $title,
                            $description,
                            'notification_sound',
                            $redirect_type
                        )
                        ->send();

                } catch (NotFound $e) {

                    // App uninstalled or token expired
                    $receiver->update([
                        'device_token' => null,
                    ]);

                    Log::warning('Invalid FCM token removed.', [
                        'user_id' => $receiver->id,
                        'token' => $receiver->device_token,
                    ]);

                } catch (\Throwable $e) {

                    // Log the error but continue saving notification
                    Log::error('FCM Error', [
                        'user_id' => $receiver->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // Save Notification
            $notification = new Notification();
            $notification->user_id = $type == 'client' ? $receiver->id : null;
            $notification->inf_id = $type == 'influencer' ? $receiver->id : null;
            $notification->admin_id = ($type == 'admin' || ($type == 'influencer' && $category == 'alert')) ? 1 : null;
            $notification->title = $title;
            $notification->description = $description;
            $notification->category = $category;
            $notification->save();

            DB::commit();

        } catch (\Throwable $th) {

            DB::rollBack();

            Log::error('Notification Save Error', [
                'message' => $th->getMessage(),
            ]);

            throw $th;
        }
    }
}
