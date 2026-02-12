function showSection(type) {
    const upcomingTab = document.getElementById("upcoming-tab");
    const ongoingTab = document.getElementById("ongoing-tab");
    const registeredTab = document.getElementById("registered-tab");
    const completedTab = document.getElementById("completed-tab");
    const upcomingSection = document.getElementById("upcoming-section");
    const ongoingSection = document.getElementById("ongoing-section");
    const registeredSection = document.getElementById("registered-section");
    const completedSection = document.getElementById("completed-section");
    if (type === "upcoming") {
        upcomingSection.classList.remove("hidden");
        ongoingSection.classList.add("hidden");
        registeredSection.classList.add("hidden");
        completedSection.classList.add("hidden");
        upcomingTab.classList.add("bg-primary", "text-white", "rounded-full");
        ongoingTab.classList.remove("bg-primary", "text-white", "rounded-full");
        registeredTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        completedTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
    } else if (type === "ongoing") {
        ongoingSection.classList.remove("hidden");
        upcomingSection.classList.add("hidden");
        registeredSection.classList.add("hidden");
        completedSection.classList.add("hidden");
        ongoingTab.classList.add("bg-primary", "text-white", "rounded-full");
        upcomingTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        registeredTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        completedTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
    } else if (type === "registered") {
        registeredSection.classList.remove("hidden");
        upcomingSection.classList.add("hidden");
        completedSection.classList.add("hidden");
        ongoingSection.classList.add("hidden");
        registeredTab.classList.add("bg-primary", "text-white", "rounded-full");
        upcomingTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        ongoingTab.classList.remove("bg-primary", "text-white", "rounded-full");
        completedTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
    } else if (type === "completed") {
        registeredSection.classList.add("hidden");
        upcomingSection.classList.add("hidden");
        completedSection.classList.remove("hidden");
        ongoingSection.classList.add("hidden");
        registeredTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        upcomingTab.classList.remove(
            "bg-primary",
            "text-white",
            "rounded-full",
        );
        ongoingTab.classList.remove("bg-primary", "text-white", "rounded-full");
        completedTab.classList.add("bg-primary", "text-white", "rounded-full");
    }
}

$(document).on("submit", "#eventForm", function (e) {
    e.preventDefault();
    let fields = [
        {
            id: "#event_title",
            condition: (val) => val === "",
            message: "Event title is required",
        },
        {
            id: "#club_id",
            condition: (val) => val === "",
            message: "Please select Club",
        },
        {
            id: "#programme_officer",
            condition: (val) => val === "",
            message: "Please select Programme Officer",
        },
        {
            id: "#description",
            condition: (val) => val === "",
            message: "Please enter Description",
        },
        {
            id: "#start_time",
            condition: (val) => val === "",
            message: "Please select Start Time",
        },
        {
            id: "#end_time",
            condition: (val) => val === "",
            message: "Please select End Time",
        },
        {
            id: "#location",
            condition: (val) => val === "",
            message: "Please enter Location",
        },
        {
            id: "#session",
            condition: (val) => val === "",
            message: "Please enter session",
        },
        {
            id: "#eligibility",
            condition: (val) => val === "",
            message: "Please enter eligibility criteria",
        },
        {
            id: "#registration_deadline",
            condition: (val) => val === "",
            message: "Please select Registration Deadline",
        },
        {
            id: "#contact_person",
            condition: (val) => val === "",
            message: "Please enter Contact Person",
        },
        {
            id: "#contact_email",
            condition: (val) => val === "",
            message: "Please enter Contact Email",
        },
        {
            id: ".seat_count",
            condition: (val) => val === "",
            message: "Please enter Seat Count",
        },
        {
            id: "#event_type",
            condition: (val) => val === "",
            message: "Please select Event Type",
        },
        {
            id: "#duration_months",
            condition: (val) => val === "",
            message: "Please Enter Duration Month",
        },
        {
            id: ".department",
            condition: (val) => val === "",
            message: "Please Select Department",
        },
        {
            id: ".section",
            condition: (val) => val === "",
            message: "Please Select Section",
        },
        {
            id: ".event_date",
            condition: (val) => val === "",
            message: "Please Select Event Date",
        },
    ];

    let isValid = true;
    for (const field of fields) {
        const result = validateField(field); // synchronous, so no async/await needed
        if (!result) isValid = false;
    }

    let eventType = $("#event_type").val();
    let price = $("#price").val();
    const errorEl = $("#price").siblings(".error-message");
    if (eventType === "paid" && (price === "" || price <= 0)) {
        $("#price").addClass("border-red-500 ring-1 ring-red-500");
        if (errorEl.length === 0) {
            $("#price").after(
                `<div class="error-message text-red-500 text-sm mt-1">Please enter valid price for Paid Event</div>`,
            );
        }
        showToast("Please enter valid price for Paid Event", "error", 2000);
        isValid = false;
    } else {
        $("#price").removeClass("border-red-500 ring-1 ring-red-500");
        if (errorEl.length) errorEl.remove();
    }
    let fileInput = $("#fileInput")[0];
    let oldBanner = $('input[name="old_banner"]').val();

    if ((!oldBanner || oldBanner === "") && fileInput.files.length === 0) {
        showToast("Event banner image is required", "error", 2000);
        $("#dropArea").addClass("border-red-500 ring-2 ring-red-500");
        isValid = false;
    } else {
        $("#dropArea").removeClass("border-red-500 ring-2 ring-red-500");
    }
    // Is Technical Event validation
    if ($('input[name="is_technical_event"]:checked').length === 0) {
        showToast("Is Technical Event field is required", "error", 2000);

        $('input[name="is_technical_event"]')
            .closest("div")
            .addClass("ring-2 ring-red-500 rounded");

        isValid = false;
    } else {
        $('input[name="is_technical_event"]')
            .closest("div")
            .removeClass("ring-2 ring-red-500 rounded");
    }

    if (!isValid) return;
    $("#submitBtn")
        .prop("disabled", true)
        .addClass("opacity-50 cursor-not-allowed")
        .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    let formData = new FormData(this);
    let taskId = "request()->task_id";
    sendRequest(
        "/admin/save-event",
        formData,
        "POST",
        function (res) {
            if (res.success) {
                showToast(res.message, "success", 2000);
                setTimeout(function () {
                    window.location.href = "/admin/event-list"; // Replace with your actual event list route
                }, 2000);
            } else {
                showToast("Something went wrong!", "error", 2000);
            }
        },
        function (err) {
            $("#submitBtn")
                .prop("disabled", false)
                .removeClass("opacity-50 cursor-not-allowed")
                .html('<i class="fas fa-save"></i> Create Event');

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

let deleteEventId = null;
let deleteButton = null;

// Open modal
$(document).on("click", ".deleteEvent", function () {
    deleteEventId = $(this).data("id");
    deleteButton = $(this);

    $("#deleteModal").removeClass("hidden").addClass("flex");
});

// Cancel button
$("#cancelDelete").on("click", function () {
    $("#deleteModal").addClass("hidden").removeClass("flex");
});

// Confirm delete
$("#confirmDelete").on("click", function () {
    $.ajax({
        url: "/admin/events/" + deleteEventId,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            if (response.success) {
                deleteButton.closest("tr").remove();
              showToast(response.message, "success", 2000);
            } else {
              showToast(response.message, "error", 2000);
            }

            $("#deleteModal").addClass("hidden").removeClass("flex");
        },
        error: function () {
            showToast("Delete failed", "error", 2000);
            $("#deleteModal").addClass("hidden").removeClass("flex");
        },
    });
});

$(document).on("change", "#club_id", function () {
    var clubId = $(this).val();
    if (clubId) {
        $.ajax({
            url: "/admin/create-event", // Route to get officers
            type: "GET",
            dataType: "json",
            data: {
                clubId: clubId,
                get_programme_officer: true,
            },
            success: function (response) {
                $("#programme_officer").empty(); // Clear previous options
                $("#programme_officer").append(
                    '<option value="">Select Programme Officer</option>',
                );
                if (response.success && response.faculty != "") {
                    var officer = response.faculty.get_faculty;
                    $("#programme_officer").append(
                        '<option value="' +
                            officer.id +
                            '">' +
                            officer.name +
                            "</option>",
                    );
                }
            },
            error: function () {
                showToast("Unable to fetch programme officers!", "error", 2000);
            },
        });
    } else {
        $("#programme_officer").empty();
        $("#programme_officer").append(
            '<option value="">Select Programme Officer</option>',
        );
    }
});

document.getElementById("event_type").addEventListener("change", function () {
    let type = this.value;
    let container = document.getElementById("priceFieldContainer");

    if (type === "paid") {
        container.innerHTML = `
            <label class="block font-medium">Price</label>
            <input type="number" name="price" id="price" placeholder="Enter price" class="bg-[#D9D9D9] w-full rounded-full py-2 px-4 focus:outline-none focus:ring focus:ring-primary/40">`;
    } else {
        container.innerHTML = "";
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const addDeptBtn = document.getElementById("addDeptBtn");
    const deptContainer = document.getElementById("departmentContainer");

    if (!addDeptBtn || !deptContainer) return;

    // IMPORTANT FIX
    let deptIndex = deptContainer.querySelectorAll(".dept-card").length;

    addDeptBtn.addEventListener("click", function () {
        addDepartmentCard();
    });

    function addDepartmentCard(data = {}) {
        const deptOptionsHtml = deptOptions
            .map(
                (d) =>
                    `<option value="${d.id}" ${data.programme_id == d.id ? "selected" : ""}>
                ${d.name}
            </option>`,
            )
            .join("");

        const card = document.createElement("div");
        card.className = "bg-[#F0F0F0] p-5 rounded-2xl relative dept-card";

        card.innerHTML = `
            <button type="button" class="rounded-2xl py-1 px-2 absolute top-2 right-5 text-red-500 font-bold removeDept bg-white">Remove</button>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
                <div>
                    <label class="block text-sm font-medium">Programme <span class="text-red-600">*</span></label>
                    <select name="departments[${deptIndex}][programme_id]"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-3 department">
                        <option value="">Select Programme</option>
                        ${deptOptionsHtml}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Section <span class="text-red-600">*</span></label>
                    <select  name="departments[${deptIndex}][section]" id="section" class="bg-[#D9D9D9] w-full p-2 border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-primary/40 section">
                                <option value="" selected disabled>Select Section</option>
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                                <option value="r">R</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Event Date <span class="text-red-600">*</span></label>
                    <input type="date"
                        name="departments[${deptIndex}][event_date]"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 event_date date_field">
                </div>
                 <div>
                            <label class="block text-sm font-medium">
                                Is Reserve Date <span class="text-red-600">*</span>
                            </label>
                            <div class="flex items-center gap-6 mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="departments[${deptIndex}][is_reserve_date]"
                                        value="y" class="text-primary focus:ring-primary is_reserve_date">
                                    <span class="ml-2 text-sm">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="departments[${deptIndex}][is_reserve_date]"
                                        value="n" class="text-primary focus:ring-primary is_reserve_date">
                                    <span class="ml-2 text-sm">No</span>
                                </label>
                            </div>
                        </div>
                <div>
                    <label class="block text-sm font-medium">Seat Count <span class="text-red-600">*</span></label>
                    <input type="number"
                        name="departments[${deptIndex}][seat_count]"
                        class="w-full bg-[#D9D9D9] rounded-full px-4 py-2 seat_count">
                </div>
            </div>
        `;

        deptContainer.appendChild(card);
        flatpickr(card.querySelectorAll(".date_field"), {
            dateFormat: "d/m/Y",
        });
        deptIndex++; // critical
    }

    // remove department (event delegation)
    deptContainer.addEventListener("click", function (e) {
        if (e.target.classList.contains("removeDept")) {
            e.target.closest(".dept-card")?.remove();
        }
    });
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
