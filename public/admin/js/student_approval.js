$(document).on("click", ".btnAction", function () {
    let id = $(this).data("id");
    let action = $(this).data("action");
    $.ajax({
        url: "nasa/admin/student-event-approval",
        type: "POST",
        data: {
            id: id,
            action: action,
        },
        success: function (response) {
           if(response.success){
               showToast(response.message, "success", 2000);
               setTimeout(() => location.reload(), 800);
           }
        },
        error: function () {
            // showToast(response.message, "error", 2000);
        },
    });
});

  document.addEventListener("DOMContentLoaded", () => {
      const studentSelect = document.querySelector('select[name="student"]');
      const statusSelect = document.querySelector('select[name="status"]');
      const eventSelect = document.querySelector('select[name="event"]');
      const tasks = document.querySelectorAll(".task-card");
      const refreshBtn = document.getElementById("refreshBtn");
      function filterTasks() {
          const studentVal = studentSelect.value;
          const statusVal = statusSelect.value;
          const eventVal = eventSelect.value;
          tasks.forEach((task) => {
              const taskStudent = task.dataset.student;
              const taskStatus = task.dataset.status;
              const taskEvent = task.dataset.event;
              const studentMatch = !studentVal || studentVal === taskStudent;
              const statusMatch = !statusVal || statusVal === taskStatus;
              const eventMatch = !eventVal || eventVal === taskEvent;
              task.style.display =
                  studentMatch && statusMatch && eventMatch ? "block" : "none";
          });
      }

      // Listen to filters
      studentSelect.addEventListener("change", filterTasks);
      statusSelect.addEventListener("change", filterTasks);
      eventSelect.addEventListener("change", filterTasks);

      // Refresh button
      refreshBtn.addEventListener("click", () => {
          // Reset all filters to default
          studentSelect.value = "";
          statusSelect.value = "";
          eventSelect.value = "";

          // Show all tasks
          tasks.forEach((task) => {
              task.style.display = "block";
          });
      });
  });
