$(document).on("click", ".btnAttendance", function () {
    let id = $(this).data("id");
    let type = $(this).data("type");
    let student_id = $(this).data("student_id");
    let event_id = $(this).data("event_id");
    $.ajax({
        url: attendanceMarkUrl,
        type: "POST",
        data: {
            id: id,
            type: type,
            student_id: student_id,
            event_id: event_id,
            _token: $("meta[name='csrf-token']").attr("content"),
        },
        success: function (response) {
            if (response.success) {
                showToast(response.message, "success", 2000);
                setTimeout(() => location.reload(), 800);
            }
        },
        error: function () {
            // showToast(response.message, "error", 2000);
        },
    });
});
