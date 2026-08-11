$(document).on("submit", "#departmentForm", function (e) {
    e.preventDefault();
    let $saveBtn = $("#department");
    // Fields to validate
    let fields = [
        {
            id: "#department_name",
            condition: (val) => val === "",
            message: "Department Name is required",
        },
        {
            id: "#department_code",
            condition: (val) => val === "",
            message: "Department Code is required",
        },
    ];
    let isValid = true;
    for (const field of fields) {
        const result = validateField(field); // synchronous, so no async/await needed
        if (!result) isValid = false;
    }
    if (!isValid) return;
      $saveBtn
          .prop("disabled", true)
          .removeClass("opacity-50 cursor-not-allowed")
          .text("Saving...");
    let formData = new FormData(this);
    sendRequest(
        saveDepartmentUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = departmentListUrl; // Replace with your actual event list route
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
        }
    );
});

$(document).on("submit", "#programmeForm", function (e) {
    e.preventDefault();
    let $saveBtn = $("#programme");
    let fields = [
        {
            id: "#programme_name",
            condition: (val) => val === "",
            message: "Programme Name is required",
        },
        {
            id: "#programme_code",
            condition: (val) => val === "",
            message: "Programme Code is required",
        },
        {
            id: "#department_id",
            condition: (val) => val === "",
            message: "Please select Department ID",
        },
        {
            id: "#graduate_type",
            condition: (val) => val === "",
            message: "Please select Graduate Type",
        },
    ];
    let isValid = true;
    for (const field of fields) {
        const result = validateField(field); // synchronous, so no async/await needed
        if (!result) isValid = false;
    }
    if (!isValid) return;
        $saveBtn
            .prop("disabled", true)
            .removeClass("opacity-50 cursor-not-allowed")
            .text("Saving...");
    let formData = new FormData(this);
    sendRequest(
        saveProgrammeUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = programmeListUrl; // Replace with your actual event list route
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
