const dialog = document.getElementById("preview-dialog");
if (dialog) dialog.showModal();

const filterForm = document.querySelector("form.filters");
if (filterForm) {
  filterForm.addEventListener("change", () => {
    const from = filterForm.from.value;
    const to = filterForm.to.value;

    if (from && to && from > to) {
      alert('Data "od" nie może być późniejsza niż "do".');
      return;
    }
    filterForm.submit();
  });
}

const uploadForm = document.querySelector("form.upload-form");
if (uploadForm) {
  uploadForm.addEventListener("change", () => uploadForm.submit());
}
