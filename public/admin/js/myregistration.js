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
            "rounded-full"
        );
    } else {
        registeredSection.classList.add("hidden");
        completedSection.classList.remove("hidden");
        completedTab.classList.add("bg-white", "text-primary", "rounded-full");
        registeredTab.classList.remove(
            "bg-white",
            "text-primary",
            "rounded-full"
        );
    }
}

$(function () {


    // === Assign event ID for upload ===
  $(document).on("click", ".upload", function () {
      const eventId = $(this).data("event_id");
      const studentId = $(this).data("student_id");

      $("#event_id").val(eventId);
      $("#student_id").val(studentId);

      filesArr = [];
      previewArea.empty().addClass("hidden");
      uploadText.removeClass("hidden");
      successBox.addClass("hidden");
      uploadBox.removeClass("hidden");

      $("#uploadModal").removeClass("hidden").addClass("flex");

      // Fetch existing uploads
      fetch(
          `/student/uploaded-proof?event_id=${eventId}&student_id=${studentId}`
      )
          .then((res) => res.json())
          .then((data) => {
              // Existing images
              if (data.proofs.length) {
                  uploadText.addClass("hidden");
                  previewArea.removeClass("hidden");

                  data.proofs.forEach((img) => {
                      previewArea.append(`
                        <div class="relative">
                            <img src="/storage/${img.file_path}" class="rounded-lg" width="100">
                        </div>
                    `);
                  });
              }

              // Existing feedback
              if (data.feedback) {
                  const ratings = JSON.parse(data.feedback.ratings);
                  Object.keys(ratings).forEach((key) => {
                      $(
                          `input[name="ratings[${key}]"][value="${ratings[key]}"]`
                      ).prop("checked", true);
                  });

                  $("#comments").val(data.feedback.comments);
              }
          });
  });


    // === View Details Modal ===
    $(document).on("click", ".view-details-btn", function () {
        const title = $(this).data("title");
        const image = $(this).data("image");
        const date = $(this).data("date");
        const start = $(this).data("start");
        const end = $(this).data("end");
        const location = $(this).data("location");
        const description = $(this).data("description");

        $("#modalTitle").text(title);
        $("#modalDescription").text(description);
        $("#modalImage").attr("src", image);
        $("#modalDate").html(`${date}`);
        $("#modalTime").html(`${start} - ${end}`);
        $("#modalLocation").html(`${location}`);

        $("#viewDetailsModal").removeClass("hidden").addClass("flex");
    });

    // === Close modals ===
    $(document).on("click", ".closeModal", function () {
        $("#uploadModal, #viewDetailsModal")
            .addClass("hidden")
            .removeClass("flex");
    });

    // === Close when clicking outside view details modal ===
    $("#viewDetailsModal").on("click", function (e) {
        if ($(e.target).is("#viewDetailsModal")) {
            $("#viewDetailsModal").addClass("hidden").removeClass("flex");
        }
    });

    // === Upload Modal Logic ===
    const modal = $("#uploadModal");
    const openModalBtn = $("#openUploadModal");
    const closeModalBtn = $("#closeModal");
    const dropArea = $("#dropArea");
    const fileInput = $("#fileInput");
    const previewArea = $("#previewArea");
    const uploadText = $("#uploadText");
    const submitUpload = $("#submitUpload");
    const successBox = $("#successBox");
    const uploadBox = $("#uploadBox");
    const uploadAnother = $("#uploadAnother");

    // track selected files in JS so we can remove items
    let filesArr = [];

    // helper: rebuild fileInput.files from filesArr
    function rebuildFileInput() {
        const dt = new DataTransfer();
        filesArr.forEach((f) => dt.items.add(f));
        fileInput[0].files = dt.files;
    }

    // helper: render previews from filesArr
   function showPreviewsFromArray() {
       previewArea.empty();

       if (filesArr.length === 0) {
           previewArea.addClass("hidden");
           uploadText.removeClass("hidden");
           return;
       }

       uploadText.addClass("hidden");
       previewArea.removeClass("hidden");

       filesArr.forEach((file, index) => {
           const reader = new FileReader();

           reader.onload = function (e) {
               const wrapper = $(`
                <div class="img-wrapper relative inline-block m-1" data-idx="${index}">
                    <img src="${e.target.result}" class="rounded-lg mx-auto mb-2" width="100" />
                    <button type="button" class="remove-img absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">&times;</button>
                    <p class="text-white text-xs truncate w-[100px]">${file.name}</p>
                </div>
            `);

               previewArea.append(wrapper);
           };

           reader.readAsDataURL(file);
       });
   }


    // === Open/Close upload modal ===
    openModalBtn.on("click", function () {
        modal.removeClass("hidden").addClass("flex");
    });

    closeModalBtn.on("click", function () {
        modal.addClass("hidden").removeClass("flex");
    });

    // === Drop area click -> open file picker ===
    dropArea.on("click", function (e) {
        e.stopPropagation();
        fileInput.trigger("click");
    });

    // prevent fileInput click from bubbling to drop area (avoid retrigger)
    fileInput.on("click", function (e) {
        e.stopPropagation();
    });

    // === Drag & Drop handlers ===
    dropArea.on("dragover", function (e) {
        e.preventDefault();
        dropArea.addClass("border-pink-400");
    });

    dropArea.on("dragleave", function (e) {
        e.preventDefault();
        dropArea.removeClass("border-pink-400");
    });

    dropArea.on("drop", function (e) {
        e.preventDefault();
        dropArea.removeClass("border-pink-400");

        const droppedFiles = Array.from(
            e.originalEvent.dataTransfer.files || []
        );
        handleNewFiles(droppedFiles);
    });

    // === When user selects files via file input ===
    fileInput.on("change", function (e) {
        const selected = Array.from(e.target.files || []);
        // replace selection (if you want to append instead, change logic)
        handleNewFiles(selected);
    });

    // central file-add handler (merge and enforce limits)
    function handleNewFiles(newFiles) {
        // append new files but avoid duplicates by name+size (simple check)
        newFiles.forEach((nf) => {
            const duplicate = filesArr.some(
                (f) =>
                    f.name === nf.name &&
                    f.size === nf.size &&
                    f.lastModified === nf.lastModified
            );
            if (!duplicate) filesArr.push(nf);
        });

        if (filesArr.length > 4) {
            showToast("Maximum 4 images only allowed!", "error", 2000);
            filesArr = filesArr.slice(0, 4);
        }

        // filter out files > 10MB
        filesArr = filesArr.filter((f) => {
            if (f.size > 10 * 1024 * 1024) {
                 showToast(`${f.name} exceeds 10MB and was skipped.`, "error", 2000);
                return false;
            }
            return true;
        });

        rebuildFileInput();
        showPreviewsFromArray();
    }

    // === Delegated remove handler (on the previewArea) ===
    previewArea.on("click", ".remove-img", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const wrapper = $(this).closest(".img-wrapper");
        const idx = parseInt(wrapper.attr("data-idx"), 10);

        if (isNaN(idx)) {
            // fallback: simply remove the wrapper and rebuild using what's left in DOM
            wrapper.remove();
        } else {
            // remove from filesArr and rebuild the input & previews
            filesArr.splice(idx, 1);
        }

        // rebuild input and re-render previews (re-indexes data-idx)
        rebuildFileInput();
        showPreviewsFromArray();

        // if no images left, reset preview area
        if (filesArr.length === 0) {
            previewArea.addClass("hidden");
            uploadText.removeClass("hidden");
            fileInput.val("");
        }
    });

    // === Submit upload (uses filesArr which is in sync with input) ===
    submitUpload.on("click", function (e) {
        e.preventDefault();

        if (!filesArr.length) {
            showToast("Please select at least one image!", "error", 2000);
            return;
        }

        if (filesArr.length > 4) {
            showToast(
                "You can upload a maximum of 4 images only!",
                "error",
                2000
            );
            return;
        }

        const formData = new FormData();
        filesArr.forEach((f) => formData.append("proof[]", f));
        formData.append("event_id", $("#event_id").val());
        formData.append("student_id", $("#student_id").val());
         $("input[name^='ratings']:checked").each(function () {
             formData.append($(this).attr("name"), $(this).val());
         });
        formData.append("comments", $("textarea[name='comments']").val());
        formData.append("_token", $('meta[name="csrf-token"]').attr("content"));

        fetch("/student/upload-proof", {
            method: "POST",
            body: formData,
        })
            .then((res) => res.json())
            .then((result) => {
                if (result.success) {
                    uploadBox.addClass("hidden");
                    successBox.removeClass("hidden");
                } else if (result.error) {
                    showToast(
                        "This image has already been uploaded!",
                        "error",
                        2000
                    );
                } else {
                    showToast("Upload failed. Try again!", "error", 2000);
                }
            })
            .catch((err) => {
                showToast("Error uploading images.", "error", 2000);
            });
    });

    // === Upload another proof ===
    uploadAnother.on("click", function () {
        successBox.addClass("hidden");
        uploadBox.removeClass("hidden");
        filesArr = [];
        rebuildFileInput();
        previewArea.empty().addClass("hidden");
        uploadText.removeClass("hidden");
    });

    // === (Optional) view-details close logic from your original code ===
    $(document).on("click", ".closeModal", function () {
        $("#uploadModal, #viewDetailsModal")
            .addClass("hidden")
            .removeClass("flex");
    });

    // === Example toast fallback if undefined ===
    if (typeof showToast !== "function") {
        window.showToast = function (msg, type, ms) {
            showToast(`${type}: ${msg}`, "error", 2000);
        };
    }
});
