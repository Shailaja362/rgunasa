$(document).on("submit", "#studentForm", function (e) {
    e.preventDefault();
     let $saveBtn = $("#student");

    let fields = [
        {
            id: "#student_name",
            condition: (val) => val === "",
            message: "Student Name is required",
        },
        {
            id: "#email",
            condition: (val) => val === "",
            message: "Email is required",
        },
        {
            id: "#mobile_number",
            condition: (val) => val === "",
            message: "Mobile Number is required",
        },
        {
            id: "#department_id",
            condition: (val) => val === "",
            message: "Please Select Department",
        },
        {
            id: "#programme_id",
            condition: (val) => val === "",
            message: "Please Select Programme",
        },
        {
            id: "#gender",
            condition: (val) => val === "",
            message: "Please Select Gender",
        },
        {
            id: "#section",
            condition: (val) => val === "",
            message: "Please Select Section",
        },
        {
            id: "#register_number",
            condition: (val) => val === "",
            message: "Register Number is Required",
        },
        {
            id: "#date_of_birth",
            condition: (val) => val === "",
            message: "Date of Birth is Required",
        },
        {
            id: "#semester",
            condition: (val) => val === "",
            message: "Semester is Required",
        },
        {
            id: "#batch",
            condition: (val) => val === "",
            message: "Batch is Required",
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
       saveStudentUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = studentListUrl; // Replace with your actual event list route
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
        }
    );
});

document
    .getElementById("fileInput")
    .addEventListener("change", function (event) {
        const file = event.target.files[0];
        const previewArea = document.getElementById("previewArea");
        const uploadText = document.getElementById("uploadText");

        if (file) {
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                showToast("Image size must not exceed 2 MB.", "error", 2000);
                this.value = "";
                previewArea.innerHTML = "";
                uploadText.style.display = "block";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                previewArea.innerHTML = `
                    <img src="${e.target.result}"
                         class="mx-auto rounded-2xl w-40 h-40 object-cover" />
                `;
            };

            reader.readAsDataURL(file);
            uploadText.style.display = "none";
        }
    });

document.getElementById("dropArea").addEventListener("click", function () {
    document.getElementById("fileInput").click();
});

$(document).on("submit", "#studentregisterForm", function (e) {
    e.preventDefault();
    let $saveBtn = $("#register");
    let fields = [
        {
            id: "#student_name",
            condition: (val) => val === "",
            message: "Student Name is required",
        },
        {
            id: "#email",
            condition: (val) => val === "",
            message: "Email is required",
        },
        {
            id: "#mobile_number",
            condition: (val) => val === "",
            message: "Mobile Number is required",
        },
        {
            id: "#department_id",
            condition: (val) => val === "",
            message: "Please Select Department",
        },
        {
            id: "#programme_id",
            condition: (val) => val === "",
            message: "Please Select Programme",
        },
        {
            id: "#gender",
            condition: (val) => val === "",
            message: "Please Select Gender",
        },
        {
            id: "#section",
            condition: (val) => val === "",
            message: "Please Select Section",
        },
        {
            id: "#register_number",
            condition: (val) => val === "",
            message: "Register Number is Required",
        },
        {
            id: "#date_of_birth",
            condition: (val) => val === "",
            message: "Date of Birth is Required",
        },
        {
            id: "#semester",
            condition: (val) => val === "",
            message: "Semester is Required",
        },
        {
            id: "#batch",
            condition: (val) => val === "",
            message: "Batch is Required",
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
        registerSave,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = "nasa/student/login"; // Replace with your actual event list route
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
