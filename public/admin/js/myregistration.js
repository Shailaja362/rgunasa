function showMyregistration(type) {
    const registeredTab = document.getElementById("registered-tab");
    const completedTab = document.getElementById("completed-tab");
    const registeredSection = document.getElementById("registered-section");
    const completedSection = document.getElementById("completed-section");

    if (type === "registered") {
        registeredSection.classList.remove("hidden");
        completedSection.classList.add("hidden");
        registeredTab.classList.add("bg-white", "text-primary", "rounded-full");
        completedTab.classList.remove(
            "bg-white",
            "text-primary",
            "rounded-full",
        );
    } else {
        registeredSection.classList.add("hidden");
        completedSection.classList.remove("hidden");
        completedTab.classList.add("bg-white", "text-primary", "rounded-full");
        registeredTab.classList.remove(
            "bg-white",
            "text-primary",
            "rounded-full",
        );
    }
}

$(function () {
    let filesArr = [];
    let existingImages = [];

    const MAX_FILES = 4;
    const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    const ALLOWED_TYPES = [
        "image/jpeg",
        "image/png",
        "application/pdf",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    ];

    /* ================= Elements ================= */
    const uploadModal = $("#uploadModal");
    const openUploadBtn = $("#openUploadModal");
    const dropArea = $("#dropArea");
    const fileInput = $("#fileInput");
    const previewArea = $("#previewArea");
    const uploadText = $("#uploadText");
    const submitUpload = $("#submitUpload");
    const successBox = $("#successBox");
    const uploadBox = $("#uploadBox");
    const uploadAnother = $("#uploadAnother");

    /* ================= Helpers ================= */

    function rebuildFileInput() {
        const dt = new DataTransfer();
        filesArr.forEach((f) => dt.items.add(f));
        fileInput[0].files = dt.files;
    }

    function fileLabel(type) {
        if (type === "application/pdf") return "PDF";
        if (type.includes("word")) return "DOC";
        if (type.includes("excel") || type.includes("sheet")) return "XLS";
        return "FILE";
    }

    function showPreviews() {
        previewArea.empty();

        if (!filesArr.length && !existingImages.length) {
            previewArea.addClass("hidden");
            uploadText.removeClass("hidden");
            return;
        }

        uploadText.addClass("hidden");
        previewArea.removeClass("hidden");

        // Existing images (unchanged behavior)
        existingImages.forEach((img, index) => {
            previewArea.append(`
                <div class="img-wrapper relative inline-block m-1"
                     data-type="existing" data-idx="${index}">
                    <img src="/storage/${img.file_path}" width="100" class="rounded-lg">
                    <button type="button"  class="remove-img absolute top-1 right-1
                        bg-red-600 text-white rounded-full w-6 h-6">&times;</button>
                    <p class="text-white text-xs truncate w-[100px]">
                        ${img.file_name}
                    </p>
                </div>
            `);
        });

        // New files (images + documents)
        filesArr.forEach((file, index) => {
            if (file.type.startsWith("image/")) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewArea.append(`
                        <div class="img-wrapper relative inline-block m-1"
                             data-type="new" data-idx="${index}">
                            <img src="${e.target.result}" width="100"
                                 class="rounded-lg">
                            <button type="button"
                                class="remove-img absolute top-1 right-1
                                bg-red-600 text-white rounded-full w-6 h-6">&times;</button>
                            <p class="text-white text-xs truncate w-[100px]">
                                ${file.name}
                            </p>
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
            } else {
                previewArea.append(`
                    <div class="img-wrapper relative inline-block m-1 text-center"
                         data-type="new" data-idx="${index}">
                        <div class="w-[100px] h-[100px]
                                    bg-white rounded-lg flex items-center
                                    justify-center font-bold text-primary">
                            ${fileLabel(file.type)}
                        </div>
                        <button type="button"
                            class="remove-img absolute top-1 right-1
                            bg-red-600 text-white rounded-full w-6 h-6">&times;</button>
                        <p class="text-white text-xs truncate w-[100px]">
                            ${file.name}
                        </p>
                    </div>
                `);
            }
        });
    }

    function handleNewFiles(newFiles) {
        const total = existingImages.length + filesArr.length + newFiles.length;

        if (total > MAX_FILES) {
            showToast("Maximum 4 files allowed!", "error", 2000);
            return;
        }

        newFiles.forEach((file) => {
            if (!ALLOWED_TYPES.includes(file.type)) {
                showToast(
                    "Only JPG, PNG, PDF, Word & Excel files allowed!",
                    "error",
                    2000,
                );
                return;
            }

            if (file.size > MAX_SIZE) {
                showToast("Each file must be under 10MB!", "error", 2000);
                return;
            }

            const duplicate = filesArr.some(
                (f) => f.name === file.name && f.size === file.size,
            );

            if (!duplicate) filesArr.push(file);
        });

        rebuildFileInput();
        showPreviews();
    }

    /* ================= Upload Proof Button ================= */
   $(document).on("click", ".upload", function () {
       const eventId = $(this).data("event_id");
       const studentId = $(this).data("student_id");
       const scheduleId = $(this).data("schedule_id");

       $("#event_id").val(eventId);
       $("#student_id").val(studentId);
       $("#schedule_id").val(scheduleId);

       filesArr = [];
       existingImages = [];

       previewArea.empty().addClass("hidden");
       uploadText.removeClass("hidden");
       successBox.addClass("hidden");
       uploadBox.removeClass("hidden");

       // Show modal
       uploadModal.removeClass("hidden").addClass("flex");

       //  Open file chooser automatically (optional)
        fetch(`/student/uploaded-proof?event_id=${eventId}&student_id=${studentId}&schedule_id=${scheduleId}`, )
           .then((res) => res.json())
           .then((data) => {
               // Existing proofs
               if (data.proofs?.length) {
                   existingImages = data.proofs;
                   uploadText.addClass("hidden");
                   previewArea.removeClass("hidden");
                   data.proofs.forEach((img, index) => {
                       previewArea.append(`
                        <div class="img-wrapper relative inline-block m-1"
                             data-type="existing" data-idx="${index}">
                            <img src="/storage/${img.file_path}" width="100" class="rounded-lg">
                            <button type="button"
                                class="remove-img absolute top-1 right-1
                                bg-red-600 text-white rounded-full w-6 h-6">&times;</button>
                            <p class="text-white text-xs truncate w-[100px]">
                                ${img.file_name}
                            </p>
                        </div>
                    `);
                   });
               }

               // Existing feedback
               if (data.feedback) {
                   const ratings = JSON.parse(data.feedback.ratings);
                   Object.keys(ratings).forEach((key) => {
                       $(
                           `input[name="ratings[${key}]"][value="${ratings[key]}"]`,
                       ).prop("checked", true);
                   });
                   $("#comments").val(data.feedback.comments);
               }
           });
   });


    /* ================= Click / Drag ================= */

    dropArea.on("dragover", (e) => {
        e.preventDefault();
        dropArea.addClass("border-pink-400");
    });

    dropArea.on("dragleave", (e) => {
        e.preventDefault();
        dropArea.removeClass("border-pink-400");
    });

    dropArea.on("drop", (e) => {
        e.preventDefault();
        dropArea.removeClass("border-pink-400");
        handleNewFiles(Array.from(e.originalEvent.dataTransfer.files || []));
    });

    fileInput.on("change", (e) => {
        handleNewFiles(Array.from(e.target.files || []));
    });

    /* ================= Remove ================= */

    previewArea.on("click", ".remove-img", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const wrapper = $(this).closest(".img-wrapper");
        const idx = parseInt(wrapper.data("idx"), 10);
        const type = wrapper.data("type");

        if (type === "existing") {
            existingImages.splice(idx, 1);
        } else {
            filesArr.splice(idx, 1);
            rebuildFileInput();
        }

        showPreviews();
    });

    /* ================= Submit ================= */

    submitUpload.on("click", function (e) {
        e.preventDefault();

        if (!filesArr.length && !existingImages.length) {
            showToast("Please upload at least one file!", "error", 2000);
            return;
        }

        if ($("#comments").val() === "") {
            showToast("Please enter a comment!", "error", 2000);
            return;
        }

        if ($("input[name^='ratings']:checked").length === 0) {
            showToast("Please select at least one rating!", "error", 2000);
            return;
        }

        const formData = new FormData();

        filesArr.forEach((f) => formData.append("proof[]", f));

        formData.append("event_id", $("#event_id").val());
        formData.append("student_id", $("#student_id").val());
        formData.append("schedule_id", $("#schedule_id").val());
        formData.append("comments", $("#comments").val());
        formData.append("_token", $('meta[name="csrf-token"]').attr("content"));

        $("input[name^='ratings']:checked").each(function () {
            formData.append($(this).attr("name"), $(this).val());
        });

        fetch("/student/upload-proof", {
            method: "POST",
            body: formData,
        })
            .then((res) => res.json())
            .then((res) => {
                if (res.success) {
                    uploadBox.addClass("hidden");
                    successBox.removeClass("hidden");
                } else {
                    showToast(res.message || "Upload failed", "error", 2000);
                }
            })
            .catch(() => {
                showToast("Server error", "error", 2000);
            });
    });

    uploadAnother.on("click", function () {
        successBox.addClass("hidden");
        uploadBox.removeClass("hidden");
        filesArr = [];
        rebuildFileInput();
        previewArea.empty().addClass("hidden");
        uploadText.removeClass("hidden");
    });

    /* ================= Close Modal ================= */

    $(document).on("click", ".closeModal", function () {
        $("#uploadModal, #viewDetailsModal")
            .addClass("hidden")
            .removeClass("flex");
    });
});
