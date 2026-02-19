$(document).on("submit", "#adminForm", function (e) {
    e.preventDefault();
    let fields = [
        {
            id: "#admin_name",
            condition: (val) => val === "",
            message: "Admin Name is required",
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
            id: "#role_id",
            condition: (val) => val === "",
            message: "Please Select Role",
        },
        {
            id: "#emp_code",
            condition: (val) => val === "",
            message: "Employee Code is Required!",
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
    let role_id = $("#role_id").val();
    let security_code = $("#security_code").val();
    const errorEl = $("#security_code").siblings(".error-message");
    if (role_id == 1 && security_code === "") {
        $("#security_code").addClass("border-red-500 ring-1 ring-red-500");
        if (errorEl.length === 0) {
            $("#security_code").after(
                `<div class="error-message text-red-500 text-sm mt-1">Please enter valid security code</div>`,
            );
        }
        isValid = false;
    } else {
        $("#security_code").removeClass("border-red-500 ring-1 ring-red-500");
        if (errorEl.length) errorEl.remove();
    }

    if (!isValid) return;
    let formData = new FormData(this);
    sendRequest(
        "/admin/save-admin",
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = "/admin/admin-list"; // Replace with your actual event list route
                }, 2000);
            } else {
                showToast(res.message, "error", 2000);
            }
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
        },
    );
});

document
    .getElementById("fileInput")
    .addEventListener("change", function (event) {
        const file = event.target.files[0];
        const previewArea = document.getElementById("previewArea");
        const uploadText = document.getElementById("uploadText");

        if (file) {
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

document.getElementById("role_id").addEventListener("change", function () {
    let type = this.value;
    let container = document.getElementById("securitycodeFieldContainer");

    if (type == 1) {
        container.innerHTML = `
            <label class="block font-medium">Security Code</label>
            <input type="text" name="security_code" id="security_code" placeholder="Please Enter your Security Code" class="bg-[#D9D9D9] w-full rounded-full py-2 px-4 focus:outline-none focus:ring focus:ring-primary/40">`;
    } else {
        container.innerHTML = "";
    }
});
