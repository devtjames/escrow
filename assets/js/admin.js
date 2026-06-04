(() => {
  "use strict";

  function syncStudyModes(programmeNode) {
    const form = programmeNode.closest("form") || document;
    const modeNode = form.querySelector('select[name="study_mode"]');
    if (!modeNode) return;
    const programme = String(programmeNode.value || "").toUpperCase();
    [...modeNode.options].forEach((option) => {
      option.hidden = programme === "HND" && String(option.value || option.textContent).toUpperCase() === "PT";
    });
    if (programme === "HND" && String(modeNode.value || "").toUpperCase() === "PT") {
      modeNode.value = "FT";
    }
  }

  function syncLevels(programmeNode) {
    const form = programmeNode.closest("form") || document;
    const levelNode = form.querySelector("[data-level-select]");
    if (!levelNode) return;
    const programme = String(programmeNode.value || "").toUpperCase();
    [...levelNode.options].forEach((option) => {
      option.hidden = programme !== "" && option.value !== "" && !String(option.value).startsWith(programme);
    });
    if (programme !== "" && levelNode.value !== "" && !String(levelNode.value || "").startsWith(programme)) {
      levelNode.value = `${programme}1`;
    }
    syncStudyModes(programmeNode);
  }

  document.querySelectorAll("[data-programme-select]").forEach(syncLevels);

  function normalizeNameInput(input) {
    input.value = input.value.replace(/[0-9]/g, "").toUpperCase();
  }

  document.querySelectorAll("[data-name-input]").forEach(normalizeNameInput);

  function syncFeeScope(feeTypeNode) {
    const form = feeTypeNode.closest("form") || document;
    const isSchoolFee = feeTypeNode.value === "school_fee";
    form.querySelectorAll("[data-fee-extra-scope]").forEach((field) => {
      field.hidden = isSchoolFee;
      if (isSchoolFee) {
        const input = field.querySelector("select, input");
        if (input) input.value = "";
      }
    });
    form.querySelectorAll("[data-fee-school-scope]").forEach((field) => {
      field.hidden = !isSchoolFee;
      if (!isSchoolFee) {
        const input = field.querySelector("select, input");
        if (input) input.value = "";
      }
    });
  }

  document.querySelectorAll("[data-fee-type-select]").forEach(syncFeeScope);

  async function syncDepartments(facultyNode) {
    const form = facultyNode.closest("form") || document;
    const target = form.querySelector("[data-department-select]");
    const programmeNode = form.querySelector("[data-programme-select]");
    if (!target) return;
    const selectedDepartment = target.value;
    if (!facultyNode.value) {
      target.innerHTML = `<option value="">All departments</option>`;
      return;
    }
    target.innerHTML = `<option value="">Loading...</option>`;
    try {
      const params = new URLSearchParams({ faculty_id: facultyNode.value });
      if (programmeNode?.value) params.set("programme_type", programmeNode.value);
      const response = await fetch(`../api/departments?${params.toString()}`, { credentials: "same-origin" });
      const payload = await response.json();
      target.innerHTML = `<option value="">All departments</option>` + (payload.departments || []).map((row) => `<option value="${row.id}">${escapeHtml(row.name)}</option>`).join("");
      if ([...target.options].some((option) => option.value === selectedDepartment)) {
        target.value = selectedDepartment;
      }
    } catch {
      target.innerHTML = `<option value="">Unable to load departments</option>`;
    }
  }

  document.querySelectorAll("[data-fee-type-select]").forEach((feeTypeNode) => {
    const facultyNode = (feeTypeNode.closest("form") || document).querySelector("[data-faculty-select]");
    if (facultyNode && facultyNode.value) {
      syncDepartments(facultyNode);
    }
  });

  document.addEventListener("change", async (event) => {
    if (event.target.matches("[data-programme-select]")) {
      syncLevels(event.target);
      const facultyNode = (event.target.closest("form") || document).querySelector("[data-faculty-select]");
      if (facultyNode?.value) syncDepartments(facultyNode);
    }

    if (event.target.matches("[data-name-input]")) {
      normalizeNameInput(event.target);
    }

    if (event.target.matches("[data-fee-type-select]")) {
      syncFeeScope(event.target);
    }

    if (!event.target.matches("[data-faculty-select]")) return;
    syncDepartments(event.target);
  });

  document.addEventListener("input", (event) => {
    if (event.target.matches("[data-name-input]")) {
      normalizeNameInput(event.target);
    }
  });

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char]));
  }
})();
