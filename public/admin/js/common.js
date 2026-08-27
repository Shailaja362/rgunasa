function initChoiceSelect(elements) {
    elements.forEach(function (el) {
        if (el.dataset.choicesInitialized) return;
        new Choices(el, {
            searchEnabled: true,
            itemSelectText: "",
            shouldSort: false,
            allowHTML: true,
            removeItemButton: true,
        });
        el.dataset.choicesInitialized = "true";
    });
}

document.addEventListener("DOMContentLoaded", function () {
    initChoiceSelect(document.querySelectorAll(".choice-select"));
});
