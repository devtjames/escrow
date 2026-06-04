(() => {
  "use strict";

  const data = window.StudentData || {
    student: {},
    wallet: { target_amount: 0, current_balance: 0, status: "active" },
    transactions: [],
    feeItems: []
  };

  const money = (value) => new Intl.NumberFormat("en-NG", { style: "currency", currency: "NGN", maximumFractionDigits: 0 }).format(Number(value || 0));
  const prettyDate = (value) => {
    if (!value) return "";
    const date = new Date(String(value).replace(" ", "T"));
    return new Intl.DateTimeFormat("en-NG", { year: "numeric", month: "short", day: "2-digit" }).format(Number.isNaN(date.getTime()) ? new Date() : date);
  };
  const target = () => Number(data.wallet?.target_amount || 0);
  const paid = () => Number(data.wallet?.current_balance || 0);
  const remaining = () => Math.max(target() - paid(), 0);
  const minimumDeposit = () => Number(window.AppConfig?.minimumDeposit || 5000);
  const minimumAllowedDeposit = () => Math.min(minimumDeposit(), remaining());
  const status = () => data.wallet?.status === "paid" || data.wallet?.status === "completed" || remaining() === 0 ? "paid" : "active";
  const progress = () => target() > 0 ? Math.min(Math.round((paid() / target()) * 100), 100) : 100;

  function setText(name, value) {
    document.querySelectorAll(`[data-text="${name}"]`).forEach((node) => { node.textContent = value; });
  }

  function refreshStudent() {
    const map = {
      studentName: data.student?.name || "",
      matric: data.student?.matric || data.student?.application || "",
      program: data.student?.program || "",
      level: data.student?.level || "",
      category: data.student?.category || "",
      session: data.student?.session || "",
      totalFee: money(target()),
      paidAmount: money(paid()),
      remainingAmount: money(remaining()),
      dueDate: prettyDate(data.student?.dueDate),
      status: status().toUpperCase(),
      progress: `${progress()}%`
    };
    Object.entries(map).forEach(([key, value]) => setText(key, value));

    document.querySelectorAll("[data-status-pill]").forEach((node) => {
      node.className = status() === "paid"
        ? "status-pill bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300"
        : "status-pill bg-green-100 text-fedpoffa-blue dark:bg-green-500/15 dark:text-green-300";
      node.textContent = status() === "paid" ? "COMPLETED" : "ACTIVE";
    });

    document.querySelectorAll("[data-progress-bar]").forEach((bar) => {
      requestAnimationFrame(() => { bar.style.width = `${progress()}%`; });
    });

    document.querySelectorAll("[data-deposit-button]").forEach((button) => {
      button.disabled = status() === "paid" || target() <= 0;
      const label = button.dataset.depositButton || "Deposit";
      button.innerHTML = status() === "paid" ? "Fee Fully Paid" : `${window.FedpoffaUI.icon("plus")} ${label}`;
    });

    renderRecentTransactions();
    renderTransactions();
    renderFeeItems();
  }

  function renderFeeItems() {
    const body = document.querySelector("[data-fee-items]");
    if (!body) return;
    body.innerHTML = (data.feeItems || []).map((item) => `
      <tr>
        <td data-label="Item">${escapeHtml(item.title || "Fee")}</td>
        <td data-label="Session">${escapeHtml(data.student?.session || "")}</td>
        <td data-label="Amount" class="text-right font-black">${money(item.amount)}</td>
      </tr>
    `).join("") || `<tr><td class="py-8 text-center font-bold text-slate-500" colspan="3">No active fees configured for your programme.</td></tr>`;
  }

  function renderRecentTransactions() {
    const list = document.querySelector("[data-recent-transactions]");
    if (!list) return;
    list.innerHTML = (data.transactions || []).slice(0, 3).map((item) => `
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between gap-3">
          <p class="font-black text-slate-950 dark:text-white">${escapeHtml(item.reference)}</p>
          <span class="text-sm font-black text-green-600 dark:text-green-300">${money(item.amount)}</span>
        </div>
        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">${prettyDate(item.created_at)} | ${escapeHtml(item.gateway)}</p>
      </div>
    `).join("") || `<p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No wallet activity yet.</p>`;
  }

  function renderTransactions() {
    const body = document.querySelector("[data-transaction-table]");
    if (!body) return;
    const search = (document.querySelector("[data-search-transactions]")?.value || "").trim().toLowerCase();
    const sort = document.querySelector("[data-sort-transactions]")?.value || "desc";
    const rows = [...(data.transactions || [])]
      .filter((item) => !search || [item.reference, item.gateway, item.status].join(" ").toLowerCase().includes(search))
      .sort((a, b) => sort === "desc" ? new Date(b.created_at) - new Date(a.created_at) : new Date(a.created_at) - new Date(b.created_at));

    body.innerHTML = rows.map((item) => `
      <tr>
        <td data-label="Reference" class="font-black text-slate-950 dark:text-white">${escapeHtml(item.reference)}</td>
        <td data-label="Date">${prettyDate(item.created_at)}</td>
        <td data-label="Method">${escapeHtml(item.gateway)}</td>
        <td data-label="Status"><span class="status-pill bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300">${escapeHtml(String(item.status).toUpperCase())}</span></td>
        <td data-label="Amount" class="text-right font-black">${money(item.amount)}</td>
        <td data-label="Receipt" class="text-right"><a class="icon-btn" href="./receipt?ref=${encodeURIComponent(item.reference)}" title="View receipt"><span data-icon="receipt"></span></a></td>
      </tr>
    `).join("") || `<tr><td class="py-8 text-center font-bold text-slate-500" colspan="6">No matching transactions</td></tr>`;
    window.FedpoffaUI.hydrateIcons();
  }

  function openDepositModal() {
    if (status() === "paid") {
      window.FedpoffaUI.toast("success", "This student has completed payment.");
      return;
    }
    if (target() <= 0) {
      window.FedpoffaUI.toast("error", "No active fee target is configured for your programme.");
      return;
    }

    window.FedpoffaUI.openModal(`
      <form class="modal-panel w-full max-w-lg rounded-lg bg-white p-5 shadow-2xl dark:bg-slate-900 sm:p-6" data-deposit-form>
        <div class="flex items-start justify-between gap-4">
          <div><h2 class="text-2xl font-black text-slate-950 dark:text-white">Add Funds</h2><p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Outstanding balance: ${money(remaining())}</p></div>
          <button class="icon-btn" data-close-modal type="button" aria-label="Close modal"><span data-icon="x"></span></button>
        </div>
        <div class="mt-5 grid gap-4">
          <label class="form-label">Amount<input class="input" name="amount" min="${minimumAllowedDeposit()}" max="${remaining()}" type="number" placeholder="${minimumAllowedDeposit()}" required><span class="form-help">Minimum deposit is ${money(minimumDeposit())}${remaining() < minimumDeposit() ? ", but your final balance can be paid in full." : ""}</span></label>
          <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800 dark:border-green-500/30 dark:bg-green-500/15 dark:text-green-200">Paystack will securely collect the payment.</div>
          <button class="btn-primary w-full" type="submit"><span data-icon="plus"></span> Continue to Paystack</button>
        </div>
      </form>
    `);
  }

  async function initializeDeposit(formNode) {
    const form = new FormData(formNode);
    const amount = Math.min(Number(form.get("amount")), remaining());
    if (!Number.isFinite(amount) || amount < minimumAllowedDeposit()) {
      window.FedpoffaUI.toast("error", `Minimum deposit is ${money(minimumAllowedDeposit())}.`);
      return;
    }
    form.set("amount", String(amount));
    form.set("csrf_token", window.AppConfig?.csrfToken || "");

    const button = formNode.querySelector("button[type='submit']");
    button.disabled = true;
    button.textContent = "Initializing...";
    try {
      const response = await fetch("./paystack_initialize", { method: "POST", body: form, credentials: "same-origin" });
      const payload = await response.json();
      if (!payload.ok) throw new Error(payload.message || "Unable to initialize payment.");
      window.location.href = payload.authorization_url;
    } catch (error) {
      window.FedpoffaUI.toast("error", error.message);
      button.disabled = false;
      button.innerHTML = `${window.FedpoffaUI.icon("plus")} Continue to Paystack`;
    }
  }

  function openReceiptModal(reference = "") {
    const transaction = (data.transactions || []).find((item) => item.reference === reference) || (data.transactions || [])[0];
    if (!transaction) {
      window.FedpoffaUI.toast("warning", "No receipt is available yet.");
      return;
    }
    window.location.href = `./receipt?ref=${encodeURIComponent(transaction.reference)}`;
  }

  function bindEvents() {
    const syncStudyModes = (programmeNode) => {
      const form = programmeNode.closest("form") || document;
      const modeNode = form.querySelector('select[name="study_mode"]');
      if (!modeNode) return;
      const programme = String(programmeNode.value || "ND").toUpperCase();
      [...modeNode.options].forEach((option) => {
        option.hidden = programme === "HND" && String(option.value || option.textContent).toUpperCase() === "PT";
      });
      if (programme === "HND" && String(modeNode.value || "").toUpperCase() === "PT") {
        modeNode.value = "FT";
      }
    };

    const syncLevels = (programmeNode) => {
      const form = programmeNode.closest("form") || document;
      const levelNode = form.querySelector("[data-level-select]");
      if (!levelNode) return;
      const programme = String(programmeNode.value || "ND").toUpperCase();
      [...levelNode.options].forEach((option) => {
        option.hidden = !String(option.value).startsWith(programme);
      });
      if (!String(levelNode.value || "").startsWith(programme)) {
        levelNode.value = `${programme}1`;
      }
      syncStudyModes(programmeNode);
    };

    document.querySelectorAll("[data-programme-select]").forEach(syncLevels);

    const normalizeNameInput = (input) => {
      input.value = input.value.replace(/[0-9]/g, "").toUpperCase();
    };

    document.querySelectorAll("[data-name-input]").forEach(normalizeNameInput);

    const awardLabel = (awardType) => {
      if (awardType === "ND") return "ND only";
      if (awardType === "HND") return "HND only";
      return "ND/HND";
    };

    const awardSupports = (awardType, programme) => awardType === "HND/ND" || awardType === programme;

    const syncProgrammeAvailability = (form) => {
      const departmentNode = form.querySelector("[data-department-select]");
      const programmeNode = form.querySelector("[data-programme-select]");
      if (!departmentNode || !programmeNode) return;
      const selectedOption = departmentNode.options[departmentNode.selectedIndex];
      const awardType = selectedOption?.dataset.awardType || "";
      [...programmeNode.options].forEach((option) => {
        option.hidden = awardType !== "" && !awardSupports(awardType, option.value);
      });
      if (awardType !== "" && !awardSupports(awardType, programmeNode.value)) {
        const firstAllowed = [...programmeNode.options].find((option) => !option.hidden);
        if (firstAllowed) programmeNode.value = firstAllowed.value;
      }
      syncLevels(programmeNode);
    };

    const syncDepartments = async (form) => {
      const facultyNode = form.querySelector("[data-faculty-select]");
      const target = form.querySelector("[data-department-select]");
      if (!target || !facultyNode) return;
      if (!facultyNode.value) {
        target.innerHTML = `<option value="">Select faculty first</option>`;
        syncProgrammeAvailability(form);
        return;
      }
      target.innerHTML = `<option value="">Loading...</option>`;
      try {
        const params = new URLSearchParams({ faculty_id: facultyNode.value });
        const response = await fetch(`../api/departments?${params.toString()}`, { credentials: "same-origin" });
        const payload = await response.json();
        target.innerHTML = `<option value="">Select department</option>` + (payload.departments || []).map((row) => `<option value="${row.id}" data-award-type="${escapeHtml(row.award_type)}">${escapeHtml(row.name)} (${awardLabel(row.award_type)})</option>`).join("");
        syncProgrammeAvailability(form);
      } catch {
        target.innerHTML = `<option value="">Unable to load departments</option>`;
        syncProgrammeAvailability(form);
      }
    };

    document.addEventListener("click", (event) => {
      if (event.target.closest("[data-open-deposit]")) openDepositModal();
      const receiptButton = event.target.closest("[data-open-receipt]");
      if (receiptButton) openReceiptModal(receiptButton.dataset.openReceipt || "");
    });

    document.addEventListener("submit", (event) => {
      if (event.target.matches("[data-deposit-form]")) {
        event.preventDefault();
        initializeDeposit(event.target);
      }
    });

    document.addEventListener("change", async (event) => {
      if (event.target.matches("[data-programme-select]")) {
        syncLevels(event.target);
      }

      if (event.target.matches("[data-name-input]")) {
        normalizeNameInput(event.target);
      }

      if (event.target.matches("[data-department-select]")) {
        syncProgrammeAvailability(event.target.closest("form") || document);
      }

      if (!event.target.matches("[data-faculty-select]")) return;
      syncDepartments(event.target.closest("form") || document);
    });

    document.addEventListener("input", (event) => {
      if (event.target.matches("[data-name-input]")) {
        normalizeNameInput(event.target);
      }
    });

    document.querySelectorAll("[data-search-transactions], [data-sort-transactions]").forEach((input) => {
      input.addEventListener("input", renderTransactions);
      input.addEventListener("change", renderTransactions);
    });
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char]));
  }

  bindEvents();
  refreshStudent();
})();
