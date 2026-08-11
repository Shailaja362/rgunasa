$(document).on("submit", "#creditPointForm", function (e) {
    e.preventDefault();
    let $saveBtn = $("#saveCreditPoints");

    let fields = [
        {
            id: "#programme_id",
            condition: (val) => val === "",
            message: "Programme is required",
        },
        {
            id: "#semester",
            condition: (val) => val === "",
            message: "Semester is required",
        },
        {
            id: "#credit_points",
            condition: (val) => val === "",
            message: "Credit Points is required",
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
        .text("Saving....");
    let formData = new FormData(this);
    sendRequest(
        saveCreditPointUrl,
        formData,
        "POST",
        function (res) {
            console.log(res);
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = creditPointListUrl; // Replace with your actual event list route
                }, 2000);
            } else {
                showToast(res.message, "error", 2000);
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
$(document).ready(function () {
    $("#credit_points").on("input", function () {
        let value = parseFloat($(this).val());

        if (value > 4) {
             showToast(
                 "Credit Points cannot be greater than 4",
                 "error",
                 2000,
             );
            $(this).val(0);
        }

        if (value < 0) {
             showToast(
                 "Credit Points cannot be negative",
                 "error",
                 2000,
             );
            $(this).val(0);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const editButtons = document.querySelectorAll(".editBtn");

    editButtons.forEach((button) => {
        button.addEventListener("click", function () {
            let id = this.dataset.id;
            let programme = this.dataset.programme;
            let semester = this.dataset.semester;
            let credit = this.dataset.credit;
            console.log(programme);
            document.getElementById("programme_id").value = programme;
            document.getElementById("semester").value = semester;
            document.getElementById("credit_points").value = credit;
            document.getElementById("credit_id").value = id;
            document.getElementById("hidden_programme_id").value = programme;
            document.getElementById("hidden_semester").value = semester;
            document.getElementById("programme_id").disabled = true;
            document.getElementById("semester").disabled = true;
            document.getElementById("saveCreditPoints").innerHTML =
                '<i class="fas fa-edit mr-2"></i> Update';
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    });
});
