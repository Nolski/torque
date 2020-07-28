$(document).ready(() => {
    $('.torque-edit-button.paragraph').click(handleParagraphEdit);
});

const handleParagraphEdit = (e) => {
    const target = $(e.target);
    const sibling = $(e.target.previousSibling);
    sibling.replaceWith(`<p><textarea name="" id="foo" type="text">${sibling[0].innerText}</textarea></p>`);
    target.replaceWith('<div class="torque-save">Save</div>');
    $('.torque-save').click(handleParagraphSave);
};

const handleParagraphSave = (e) => {
    const target = $(e.target);
    const sibling = $(e.target.previousSibling);
    const newValue = sibling.find('textarea')[0].value;
    console.log(sibling);
    sibling.replaceWith(`<p>${newValue}</p>`);
    target.replaceWith(`<div class="torque-edit-button paragraph"><div class="torque-edit"></div></div>`);
    $('.torque-edit-button.paragraph').click(handleParagraphEdit);
};