$(document).ready(() => {
    $(".torque-edit-button.paragraph").click(handleParagraphEdit);
    $(".torque-edit-button.list").click(handleListEdit);
});

async function submitEdit(field, value) {
    const [sheetName, , key] = $("#page-info")
        .data("location")
        .replace(".mwiki", "")
        .split("/");
    const postData = {
        newValues: JSON.stringify({ [field]: value }),
        sheetName: sheetName,
        key: key,
        title: mw.config.values.wgTitle,
    };
    try {
        // Gets CORS token for POST
        const corsResult = await $.ajax({
            type: "GET",
            url: "/lfc/api.php?action=query&format=json&meta=tokens",
            dataType: "json",
        });

        const token = encodeURIComponent(corsResult.query.tokens.csrftoken);
        const actionName = "torquedataconnectsubmitedit";
        await $.ajax({
            type: "POST",
            url: `/lfc/api.php?action=${actionName}&format=json&token=${token}`,
            data: postData,
            dataType: "json",
        });
    } catch (error) {
        console.error(error);
    }
}

const textArea = (v) => $(`<textarea name="" type="text">${v}</textarea>`);
// Returns a jquery cancel and save button side-by-side
const saveButtons = (fieldName, originalValue, onCancel, onSave) => {
    const cancelBtn = $('<span class="torque-save-cancel">Cancel</span>');
    cancelBtn.data("original", originalValue);
    cancelBtn.click(onCancel);
    const saveBtn = $('<span class="torque-save">Save</span>');
    saveBtn.data("field", fieldName);
    saveBtn.click(onSave);
    return $('<div class="torque-save-wrapper"></div>')
        .append(cancelBtn)
        .append(saveBtn);
};
const editButton = (type, field) => {
    return $(
        `<div class="torque-edit-button"><div class="torque-edit"></div></div>`
    )
        .data("type", type)
        .data("field", field);
};

// Paragraph event listeners
const handleParagraphEdit = (e) => {
    const target = $(e.currentTarget);
    const sibling = $(e.currentTarget.previousSibling);
    sibling.replaceWith(textArea(sibling[0].innerText));
    target.replaceWith(
        saveButtons(
            target.data("field"),
            sibling[0].innerText,
            handleParagraphSaveCancel,
            handleParagraphSave
        )
    );
};

const substituteParagraphValue = (sibling, target, newValue) => {
    sibling.replaceWith(`<p>${newValue}</p>`);
    const editBtn = editButton("paragraph", target.data("field"));
    editBtn.click(handleParagraphEdit);
    target.replaceWith(editBtn);
};

const handleParagraphSaveCancel = (e) => {
    const target = $(e.target);
    const sibling = $(e.target).parent().prev();
    const newValue = target.data("original");
    substituteParagraphValue(sibling, target.parent(), newValue);
};

const handleParagraphSave = (e) => {
    const target = $(e.target);
    const sibling = $(e.target).parent().prev();
    const newValue = sibling[0].value;
    submitEdit(target.data("field"), newValue);
    substituteParagraphValue(sibling, target.parent(), newValue);
};

// Unordered list event listeners
const handleListEdit = (e) => {
    const clickedButton = $(e.currentTarget);
    let dataField = $(e.currentTarget).prev();
    let listElements = [];
    while (dataField[0].nodeName == "UL") {
        listElements.unshift(dataField);
        dataField = dataField.prev();
    }

    const val = listElements.map((e) => e[0].innerText);

    for (let e of listElements) {
        e.remove();
    }

    clickedButton.replaceWith(
        textArea(val.join("\n")).add(
            saveButtons(
                clickedButton.data("field"),
                val.join("\n"),
                handleListSaveCancel,
                handleListSave
            )
        )
    );
};

const substituteListValue = (sibling, target, newValue) => {
    let listElements = "";
    for (let l of newValue.split("\n")) {
        listElements += `<ul><li>${l}</li></ul>`;
    }
    sibling.replaceWith(listElements);
    const btn = $(editButton("list", target.data("field")));
    btn.click(handleListEdit);
    target.replaceWith(btn);
};

const handleListSaveCancel = (e) => {
    const target = $(e.target);
    const sibling = $(e.target).parent().prev();
    const newValue = target.data("original");
    substituteListValue(sibling, target.parent(), newValue);
};

const handleListSave = (e) => {
    const target = $(e.target);
    const sibling = $(e.target).parent().prev();
    const newValue = sibling[0].value;
    submitEdit(target.data("field"), newValue.split("\n"));
    substituteListValue(sibling, target.parent(), newValue);
};
