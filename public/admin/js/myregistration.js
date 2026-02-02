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
    let removedExistingIds = [];

    const MAX_FILES = 4;
    const MAX_SIZE = 10 * 1024 * 1024;

    const ALLOWED_TYPES = [
        "image/jpeg",
        "image/png",
        "application/pdf",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    ];

    function fileLabel(type) {
        if (type === "application/pdf") return "PDF";
        if (type.includes("word")) return "DOC";
        if (type.includes("excel") || type.includes("sheet")) return "XLS";
        return "FILE";
    }

    function showPreviews() {
        const previewArea = $("#previewArea");
        previewArea.empty();

        if (!filesArr.length && !existingImages.length) {
            previewArea.addClass("hidden");
            $("#uploadText").removeClass("hidden");
            return;
        }

        $("#uploadText").addClass("hidden");
        previewArea.removeClass("hidden");

        // Existing images
        existingImages.forEach((img, index) => {
            const isImage = img.file_type.match(/jpg|jpeg|png/i);
            previewArea.append(`
                <div class="img-wrapper relative inline-block m-2"
                     data-type="existing" data-idx="${index}">
                    ${
                        isImage
                            ? `<img src="/storage/${img.file_path}" class="w-24 h-24 object-cover rounded-lg">`
                            : `<div class="w-24 h-24 flex items-center justify-center bg-gray-200 text-white text-sm rounded-lg">${fileLabel(img.file_type)}</div>`
                    }
                    <button type="button" class="remove-img absolute -top-2 -right-2
                        bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold">×</button>
                    <p class="text-xs truncate w-24 mt-1">${img.file_name}</p>
                </div>
            `);
        });

        // New files
        filesArr.forEach((file, index) => {
            const wrapper = $(
                `<div class="img-wrapper relative inline-block m-2" data-type="new" data-idx="${index}"></div>`,
            );

            if (file.type.startsWith("image/")) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    wrapper.append(
                        `<img src="${e.target.result}" class="w-24 h-24 object-cover rounded-lg">`,
                    );
                    wrapper.append(`<button type="button" class="remove-img absolute -top-2 -right-2
                        bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold">×</button>`);
                    wrapper.append(
                        `<p class="text-xs truncate w-24 mt-1">${file.name}</p>`,
                    );
                };
                reader.readAsDataURL(file);
            } else {
                wrapper.append(
                    `<div class="w-24 h-24 flex items-center justify-center bg-gray-200 text-white text-sm rounded-lg">${fileLabel(file.type)}</div>`,
                );
                wrapper.append(`<button type="button" class="remove-img absolute -top-2 -right-2
                    bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold">×</button>`);
                wrapper.append(
                    `<p class="text-xs truncate w-24 mt-1">${file.name}</p>`,
                );
            }

            previewArea.append(wrapper);
        });
    }

    function handleNewFiles(newFiles) {
        const total = existingImages.length + filesArr.length + newFiles.length;
        if (total > MAX_FILES) {
            showToast("Maximum 4 files allowed", "error", 2000);
            return;
        }

        newFiles.forEach((file) => {
            if (!ALLOWED_TYPES.includes(file.type)) return;
            if (file.size > MAX_SIZE) return;

            const duplicate = filesArr.some(
                (f) => f.name === file.name && f.size === file.size,
            );
            if (!duplicate) filesArr.push(file);
        });

        showPreviews();
    }

    /* ================= OPEN MODAL ================= */
    $(document).on("click", ".upload", function (e) {
        e.preventDefault();

        const eventId = $(this).data("event_id");
        const studentId = $(this).data("student_id");
        const scheduleId = $(this).data("schedule_id");

        $("#event_id").val(eventId);
        $("#student_id").val(studentId);
        $("#schedule_id").val(scheduleId);

        filesArr = [];
        existingImages = [];
        removedExistingIds = [];

        $("#previewArea").empty().addClass("hidden");
        $("#uploadText").removeClass("hidden");
        $("#uploadBox").removeClass("hidden");
        $("#successBox").addClass("hidden");

        $("#uploadModal").removeClass("hidden").addClass("flex");

        fetch(
            `/student/uploaded-proof?event_id=${eventId}&student_id=${studentId}&schedule_id=${scheduleId}`,
        )
            .then((res) => res.json())
            .then((data) => {
                if (data.proofs?.length) {
                    existingImages = data.proofs;
                    showPreviews();
                }

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

    /* ================= DROP / FILE ================= */
    $("#dropArea").on("click", function (e) {
        if (
            $(e.target).closest(".remove-img").length ||
            $(e.target).closest(".img-wrapper").length
        )
            return;
        $("#fileInput")[0].click();
    });

    $("#dropArea").on("dragover", function (e) {
        e.preventDefault();
        $(this).addClass("border-pink-400");
    });

    $("#dropArea").on("dragleave", function (e) {
        e.preventDefault();
        $(this).removeClass("border-pink-400");
    });

    $("#dropArea").on("drop", function (e) {
        e.preventDefault();
        $(this).removeClass("border-pink-400");
        handleNewFiles([...e.originalEvent.dataTransfer.files]);
    });

    $("#fileInput").on("change", function () {
        handleNewFiles([...this.files]);
        $(this).val(""); // reset input
    });

    /* ================= REMOVE FILE ================= */
    $("#previewArea").on("click", ".remove-img", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const wrapper = $(this).closest(".img-wrapper");
        const idx = wrapper.data("idx");
        const type = wrapper.data("type");

        if (type === "existing") {
            removedExistingIds.push(existingImages[idx].id);
            existingImages.splice(idx, 1);
        } else {
            filesArr.splice(idx, 1);
        }

        showPreviews();
    });

    /* ================= SUBMIT ================= */
    $("#submitUpload").on("click", function (e) {
        e.preventDefault();

        if (!filesArr.length && !existingImages.length) {
            showToast("Please upload at least one file", "error", 2000);
            return;
        }
        if ($("#comments").val() == "") {
            showToast("Please enter your comments", "error", 2000);
            return;
        }
        const formData = new FormData();
        filesArr.forEach((f) => formData.append("proof[]", f));
        removedExistingIds.forEach((id) =>
            formData.append("removed_proofs[]", id),
        );
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
                    $("#uploadBox").addClass("hidden");
                    $("#successBox").removeClass("hidden");
                } else {
                    showToast("Upload failed", "error", 2000);
                }
            });
    });

    /* ================= CLOSE MODAL ================= */
    $(".closeModal").on("click", function () {
        $("#uploadModal").addClass("hidden").removeClass("flex");
    });
});
