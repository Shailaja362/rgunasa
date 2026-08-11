// $(document).on("change", "#event_id", function () {
//     var eventId = $(this).val();
//     if (eventId) {
//         $.ajax({
//             url: "/admin/create_report", // Route to get officers
//             type: "GET",
//             dataType: "json",
//             data: {
//                 eventId: eventId,
//                 get_event_date: true,
//             },
//             success: function (response) {
//                 $("#event_date").empty();
//                 $("#event_date").val(response.event.event_date);
//             },
//             error: function () {
//                 showToast("Unable to fetch event date!", "error", 2000);
//             },
//         });
//     } else {
//         $("#event_date").empty();

//     }
// });

$(document).on("submit", "#eventReportForm", function (e) {
    const totalImages = document.querySelectorAll(
        "#previewArea .img-wrapper",
    ).length;
    e.preventDefault();
    let $saveBtn = $("#report");

    // Fields to validate
    let fields = [
        {
            id: "#event_id",
            condition: (val) => val === "",
            message: "please select event",
        },
        {
            id: "#outcome_results",
            condition: (val) => val === "",
            message: "please select priority",
        },
        {
            id: "#feedback_summary",
            condition: (val) => val === "",
            message: "Please Enter Feedback Summary",
        },
        {
            id: "#programme_id",
            condition: (val) => val === "",
            message: "please select programme",
        },
        {
            id: "#section",
            condition: (val) => val === "",
            message: "Section field is required",
        },
        {
            id: "#batch",
            condition: (val) => val === "",
            message: "Batch field is required",
        },
        {
            id: "#semester",
            condition: (val) => val === "",
            message: "Semester field is required",
        },
        {
            id: "#event_date",
            condition: (val) => val === "",
            message: "Event Date field is required",
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
        .text("Saving....");
    let formData = new FormData(this);

    sendRequest(
        savereportUrl,
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = reports; // Replace with your actual event list route
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

document.addEventListener("DOMContentLoaded", function () {
    // Variables
    const dropArea = document.getElementById("dropArea");
    const fileInput = document.getElementById("fileInput");
    const previewArea = document.getElementById("previewArea");
    let filesArr = [];

    const allowedCertificate = ["jpg", "jpeg", "png", "pdf"];
    const allowedAttendance = ["jpg", "jpeg", "png", "pdf", "docx", "xlsx"];

    // Drag & Drop / Click Upload
    dropArea.addEventListener("click", () => fileInput.click());

    fileInput.addEventListener("change", function () {
        if (this.files.length > 0) handleFiles(this.files);
    });

    dropArea.addEventListener("dragover", function (e) {
        e.preventDefault();
        dropArea.classList.add("border-purple-500");
    });
    dropArea.addEventListener("dragleave", function () {
        dropArea.classList.remove("border-purple-500");
    });
    dropArea.addEventListener("drop", function (e) {
        e.preventDefault();
        dropArea.classList.remove("border-purple-500");
        if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        let currentCount = document.querySelectorAll(
            "#previewArea .img-wrapper:not([data-existing])",
        ).length;
        [...files].forEach((file) => {
            if (currentCount >= 4) {
                showToast("You can upload only 4 images.", "error", 2000);
                return;
            }
            let ext = file.name.split(".").pop().toLowerCase();
            if (!["jpg", "jpeg", "png"].includes(ext)) {
                showToast("Only JPG/PNG images allowed!", "error", 2000);
                return;
            }
            filesArr.push(file);
            previewFile(file);
            currentCount++;
        });
        refreshFileInput();
    }

    function previewFile(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const wrapper = document.createElement("div");
            wrapper.className =
                "img-wrapper relative inline-block w-full h-32 overflow-hidden rounded-lg shadow";
            wrapper.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover" />
                <button type="button" class="remove-img absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 text-sm">&times;</button>
                <p class="text-gray-800 text-xs truncate w-full text-center">${file.name}</p>
            `;
            previewArea.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    }

    // Remove Images
    previewArea.addEventListener("click", function (e) {
        if (!e.target.classList.contains("remove-img")) return;
        const wrapper = e.target.closest(".img-wrapper");
        const existingId = wrapper.dataset.existing;
        if (existingId) {
            let removed = document.getElementById("removedImages").value;
            removed = removed ? JSON.parse(removed) : [];
            removed.push(existingId);
            document.getElementById("removedImages").value =
                JSON.stringify(removed);
        } else {
            const fileName = wrapper.querySelector("p").innerText;
            filesArr = filesArr.filter((f) => f.name !== fileName);
        }
        wrapper.remove();
        refreshFileInput();
    });

    function refreshFileInput() {
        const dt = new DataTransfer();
        filesArr.forEach((f) => dt.items.add(f));
        fileInput.files = dt.files;
    }

    // Supporting files
    document.querySelectorAll(".upload-box").forEach((box) => {
        box.addEventListener("click", () => {
            const inputId = box.dataset.input;
            document.getElementById(inputId).click();
        });
    });

    document.querySelectorAll(".proof_files").forEach((input) => {
        input.addEventListener("change", function () {
            const file = this.files[0];
            const previewBox = this.parentElement.querySelector(".preview");
            const type = this.parentElement.dataset.type;
            if (!file) return;
            const ext = file.name.split(".").pop().toLowerCase();
            let valid =
                type === "certificate"
                    ? allowedCertificate.includes(ext)
                    : allowedAttendance.includes(ext);
            if (!valid) {
                showToast("Invalid file type!", "error", 2000);
                this.value = "";
                previewBox.innerHTML = "";
                return;
            }

            if (["jpg", "jpeg", "png"].includes(ext)) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewBox.innerHTML = `<img src="${e.target.result}" class="w-20 h-20 object-cover mx-auto rounded"><p class="mt-2 text-xs truncate">${file.name}</p>`;
                };
                reader.readAsDataURL(file);
            } else {
                let icon = "📄";
                if (ext === "pdf") icon = "📕 PDF";
                if (ext === "docx") icon = "📘 DOCX";
                if (ext === "xlsx") icon = "📗 XLSX";
                previewBox.innerHTML = `<div class="text-center"><p class="text-2xl">${icon}</p><p class="mt-1 text-xs truncate">${file.name}</p></div>`;
            }
        });
    });

    function showToast(msg, type = "success", dur = 3000) {
        let t = document.createElement("div");
        t.className = `fixed bottom-5 right-5 px-4 py-2 rounded shadow-lg text-white ${
            type === "error" ? "bg-red-600" : "bg-green-600"
        }`;
        t.innerText = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), dur);
    }
});

let removedImageIds = [];

$(document).on("click", ".remove-img", function (e) {
    e.preventDefault();
    let wrapper = $(this).closest(".img-wrapper");
    let imgId = wrapper.data("existing");
    if (imgId) {
        removedImageIds.push(imgId);
        $("#removedImages").val(JSON.stringify(removedImageIds));
    }
    wrapper.remove();
});
