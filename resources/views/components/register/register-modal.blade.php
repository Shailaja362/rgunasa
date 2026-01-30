<!-- Modal wrapper: fixed, full screen, centered using flex -->
<div id="registerModal" class="registerModal fixed inset-0 hidden z-50 bg-[rgba(128,128,128,0.4)] bg-opacity-50 flex items-center justify-center">
    <!-- Modal container -->
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-md mx-3 p-6 relative">
        <!-- Close Button -->
    <button type="button" onclick="document.querySelector('.registerModal').classList.add('hidden')" class="absolute top-3 right-3 text-gray-600 hover:text-gray-800 text-xl">&times;</button>
        <!-- Modal Content -->
        <h2 class="text-xl font-semibold text-primary mb-4 text-center">Register for Event</h2>
        <form id="eventForm" action="{{ route('student_register_event') }}" method="POST" class="space-y-4">
            @csrf
             <input type="hidden" name="event_id" class="event_id">
             <input type="hidden" name="stu_id" class="stu_id">
             <input type="hidden" name="schedule_id" class="schedule_id">
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" required disabled
                       class="name w-full border border-primary bg-[rgba(128,128,128,0.4)] rounded-lg px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Student ID</label>
                <input type="text" name="student_id" required disabled
                       class="student_id w-full border border-primary bg-[rgba(128,128,128,0.4)] rounded-lg px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" required disabled
                       class="email w-full border border-primary bg-[rgba(128,128,128,0.4)] rounded-lg px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
                <input type="text" name="phone" required  disabled
                       class="phone w-full border border-primary bg-[rgba(128,128,128,0.4)]  rounded-lg px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <div>
             <label class="block text-sm font-medium text-gray-700">Event Name</label>
                <input type="event" name="event" required disabled
                       class="event w-full border border-primary bg-[rgba(128,128,128,0.4)] rounded-lg px-3 py-2 mt-1 focus:outline-none focus:ring focus:ring-primary/40">
            </div>
            <button type="submit"
                    class="w-full bg-gradient-to-r from-primary to-pink-600 text-white font-semibold py-2 rounded-full hover:bg-primary/90 transition">
                Register for Event
            </button>
        </form>
    </div>
</div>

<!-- Success Modal -->


<div id="successModal" class="registerSuccessBox fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
  <div class="relative bg-white rounded-3xl shadow-xl w-[420px] text-center p-10">
    <!-- Close button -->
    <button
      type="button"
      onclick="document.querySelector('.registerSuccessBox').classList.add('hidden')"
      class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold"
    >
      &times;
    </button>

    <!-- Success Icon -->
    <div class="flex justify-center mb-6">
      <div class="bg-green-100 rounded-full p-4">
         <img src="{{ asset('/images/successfull.png') }}" width="70" />
      </div>
    </div>

    <!-- Heading -->
    <h2 class="text-2xl font-semiboldmb-2">Registration Successful! 🎉</h2>
    <p class="text-sm">You have successfully registered for the event.</p>

    <!-- Buttons -->
    <div class="flex flex-col gap-3">
      <a href="{{ route('student_dashboard') }}"
         class="bg-primary text-white px-6 py-2.5 rounded-full font-medium transition">
        Go to Dashboard
      </a>
      <a href="{{ route('my_register_events') }}"
         class="border border-primary text-primary  px-6 py-2.5 rounded-full font-medium transition">
        View Registered Events
      </a>
    </div>

    <!-- Footer Text -->
    <div class="mt-8 text-sm">
      <p>You’ll receive a confirmation email shortly,Make sure to mark</p>
      <p>your attendance on the event day to get your certificate.</p>
    </div>
  </div>
</div>




