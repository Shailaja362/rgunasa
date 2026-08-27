$(document).on("submit", "#facultyForm", function (e) {
    e.preventDefault();
     let $saveBtn = $("#faculty");

    // Fields to validate
    let fields = [
        {
            id: "#faculty_name",
            condition: (val) => val === "",
            message: "Faculty Name is required",
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
            id: "#faculty_code",
            condition: (val) => val === "",
            message: "Faculty Code is required",
        },
        {
            id: "#department_id",
            condition: (val) => val === "",
            message: "Please Select Department",
        },
        {
            id: "#designation_id",
            condition: (val) => val === "",
            message: "Please Select Designation",
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
        saveFacultyUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = facultyListUrl; // Replace with your actual event list route
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

