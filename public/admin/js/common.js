document.addEventListener("DOMContentLoaded", function () {
    const elements = document.querySelectorAll(".choice-select");
    elements.forEach(function (el) {
        new Choices(el, {
            searchEnabled: true,
            itemSelectText: "",
            shouldSort: false,
            allowHTML: true,
            removeItemButton: true,
        });
    });
});
