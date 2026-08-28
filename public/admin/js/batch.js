$(document).on("submit", "#batchForm", function (e) {
    e.preventDefault();
    let form = this;
    let $saveBtn = $("#batch");
    let fields = [
        {
            id: "#batch_name",
            condition: (val) => {
                const regex = /^\d{4}-\d{4}$/;
                if (val === "" || !regex.test(val)) {
                    return true;
                }
                const [start, end] = val.split("-").map(Number);
                return end <= start;
            },
            message:
                "Batch must be in YYYY-YYYY format and end year must be greater than start year",
        },
    ];
    let isValid = true;
    for (const field of fields) {
        const result = validateField(field);
        if (!result) isValid = false;
    }
    if (!isValid) return;

    function proceedWithSave() {
        $saveBtn
            .prop("disabled", true)
            .removeClass("opacity-50 cursor-not-allowed")
            .text("Saving...");
        let formData = new FormData(form);
        sendRequest(
            saveBatchUrl,
            formData,
            "POST",
            function (res) {
                if (res.success) {
                    showToast(res.message, "success", 2000);
                    setTimeout(function () {
                        window.location.href = batchListUrl;
                    }, 2000);
                } else {
                    showToast(res.message || "Something went wrong!", "error", 2000);
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
    }

    let batchId = $('input[name="batch_id"]').val();
    if (batchId) {
        Swal.fire({
            title: "Are you sure?",
            text: "Updating this batch will also update it wherever it is already used (student records and event schedules). Do you want to continue?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, update it!",
            cancelButtonText: "Cancel",
        }).then(function (result) {
            if (result.isConfirmed) {
                proceedWithSave();
            }
        });
    } else {
        proceedWithSave();
    }
});
