$(document).on("submit", "#clubForm", function (e) {
    e.preventDefault();
    // Fields to validate
    let $saveBtn = $("#club");
    let fields = [
        {
            id: "#club_name",
            condition: (val) => val === "",
            message: "Club Name is required",
        },
        {
            id: "#faculty_id",
            condition: (val) => val === "",
            message: "Please select Faculty",
        },
    ];
    let isValid = true;
    for (const field of fields) {
        const result = validateField(field);
        if (!result) isValid = false;
    }
    if (!isValid) return;
       $saveBtn
           .prop("disabled", true)
           .removeClass("opacity-50 cursor-not-allowed")
           .text("Saving...");
    let formData = new FormData(this);
    sendRequest(
        saveClubUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = clubListUrl; // Replace with your actual event list route
                }, 2000);
            } else {
                showToast("Something went wrong!", "error", 2000);
            }
            $saveBtn
                .prop("disabled", false)
                .removeClass("opacity-50 cursor-not-allowed")
                .text("Save");
        },
        function (err) {
            if (err.errors) {
                let msg = "";
                $.each(err.errors, function (k, v) {
                    msg += v[0] + "<br>";
                });
                showToast(msg, "error", 2000);
            } else {
                showToast(err.message || "Unexpected error", "error", 2000);
            }
            $saveBtn
                .prop("disabled", false)
                .removeClass("opacity-50 cursor-not-allowed")
                .text("Save");
        },
    );
});
