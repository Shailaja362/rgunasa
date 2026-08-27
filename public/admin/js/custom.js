/**
 * Common AJAX Helper (Fetch-based)
 * @param {string} url - API endpoint (Laravel route)
 * @param {FormData|Object} data - Request data
 * @param {string} method - HTTP method (default: POST)
 * @param {function} onSuccess - Callback for success
 * @param {function} onError - Callback for errors
 */
async function sendRequest(
    url,
    data,
    method = "POST",
    onSuccess = null,
    onError = null
) {
    try {
        let options = {
            method: method,
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
        };

        // If FormData, don’t set Content-Type
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.body = JSON.stringify(data);
            options.headers["Content-Type"] = "application/json";
        }

        let res = await fetch(url, options);
        let responseData = await res.json();

        if (res.ok) {
            if (onSuccess) onSuccess(responseData);
        } else {
            if (onError) onError(responseData);
        }
    } catch (err) {
        if (onError) onError({ message: "Something went wrong." });
    }
}

/**
 * Common Input validate error Helper (Fetch-based)
 * @param field
 * @returns {boolean}
 */
function validateField(field) {
    let isValid = true;
    $(field.id).each(function () {
        const element = $(this);
        const rawValue = element.val();
        const value = Array.isArray(rawValue)
            ? rawValue
            : typeof rawValue === "string"
                ? rawValue.trim()
                : (rawValue ?? "");
        const errorEl = element.siblings(".error-message");

        if (field.condition(value)) {
            isValid = false;
            element.addClass("border-red-500 ring-1 ring-red-500");
            if (errorEl.length === 0) {
                element.after(
                    `<div class="error-message text-red-500 text-sm mt-1">${field.message}</div>`,
                );
            } else {
                errorEl.text(field.message);
            }
        } else {
            element.removeClass("border-red-500 ring-1 ring-red-500");
            if (errorEl.length) errorEl.remove();
        }
    });

    return isValid;
}


/**
 * Common Toast Helper (Fetch-based)
 * @param message
 * @param type
 * @param duration
 */
function showToast(message, type = "success", duration = 3000) {
    const colors = {
        success: "bg-white text-primary border border-primary",
        error: "bg-white text-red-500 border border-red-500",
        warning: "bg-white text-blue-500 border border-blue-500",
    };

    const icons = {
        success: `<svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="#9F3895" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                  </svg>`,
        error: `<svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="red" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>`,
        warning: `<svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="blue" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>`,
    };

    const toast = document.createElement("div");
    toast.className = `${colors[type]} px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 max-w-sm w-full font-medium animate-slide-in`;
    toast.innerHTML = `${icons[type]}<span>${message}</span>`;

    // Hover pause
    toast.addEventListener("mouseenter", () => clearTimeout(toast.timeout));
    toast.addEventListener("mouseleave", () => startTimeout());

    const container = document.getElementById("toast-container");
    container.appendChild(toast);

    function startTimeout() {
        toast.timeout = setTimeout(() => {
            toast.classList.add(
                "opacity-0",
                "transition",
                "duration-500",
                "translate-x-2"
            );
            setTimeout(() => toast.remove(), 500);
        }, duration);
    }

    startTimeout();
}
