const SELECT_ALL_VALUE = "__select_all__";

function initChoiceSelect(elements) {
    elements.forEach(function (el) {
        if (el.dataset.choicesInitialized) return;
        el.choicesInstance = new Choices(el, {
            searchEnabled: true,
            itemSelectText: "",
            shouldSort: false,
            allowHTML: true,
            removeItemButton: true,
        });
        el.dataset.choicesInitialized = "true";

        if (el.classList.contains("select-all-enabled")) {
            el.addEventListener("addItem", function (e) {
                if (e.detail.value !== SELECT_ALL_VALUE) return;
                const allValues = Array.from(el.options)
                    .map((o) => o.value)
                    .filter((v) => v && v !== SELECT_ALL_VALUE);
                el.choicesInstance.removeActiveItemsByValue(SELECT_ALL_VALUE);
                el.choicesInstance.setChoiceByValue(allValues);
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    initChoiceSelect(document.querySelectorAll(".choice-select"));
});
