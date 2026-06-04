(function () {
  "use strict";

  const icons = {
    dashboard: '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
    receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2z"></path><path d="M8 7h8"></path><path d="M8 11h8"></path><path d="M8 15h5"></path>',
    wallet: '<path d="M20 7H5a2 2 0 0 1 0-4h12"></path><path d="M4 7v12a2 2 0 0 0 2 2h14V7"></path><path d="M16 14h.01"></path>',
    calendar: '<path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18"></path>',
    building: '<path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9h1"></path><path d="M9 13h1"></path><path d="M9 17h1"></path>',
    book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path>',
    mail: '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path><path d="m22 6-10 7L2 6"></path>',
    settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.5a2 2 0 0 1-1 1.72l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.72v-.5a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle>',
    edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>',
    chevron: '<path d="m6 9 6 6 6-6"></path>',
    logout: '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path>',
    menu: '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>',
    panel: '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path><path d="m16 15-3-3 3-3"></path>',
    sun: '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path>',
    moon: '<path d="M20.99 13.25A8.5 8.5 0 1 1 10.75 3.01 7 7 0 0 0 20.99 13.25z"></path>'
  };

  const nav = [
    ["dashboard", "dashboard", "Dashboard", "dashboard.html"],
    ["students", "users", "Students", "students.html"],
    ["payments", "receipt", "Payments", "payments.html"],
    ["fees", "wallet", "Fees", "fees.html"],
    ["emails", "mail", "Email Center", "emails.html"],
    ["settings", "settings", "Settings", "settings.html"]
  ];

  const groups = [
    { icon: "building", label: "Academic Setup", pages: [["sessions", "calendar", "Sessions", "sessions.html"], ["faculties", "building", "Faculties", "faculties.html"], ["departments", "book", "Departments", "departments.html"]] },
    { icon: "edit", label: "Record Editors", pages: [["student-edit", "users", "Student Biodata", "student-edit.html"], ["fee-edit", "wallet", "Fee Editor", "fee-edit.html"], ["session-edit", "calendar", "Session Editor", "session-edit.html"], ["faculty-edit", "building", "Faculty Editor", "faculty-edit.html"], ["department-edit", "book", "Department Editor", "department-edit.html"]] }
  ];

  const moneyRows = [
    ["ADEBAYO MARIAM", "ND1 Computer Science", "FPO-20260602-A84C", "SUCCESS", "NGN 45,000"],
    ["OLADIMEJI KUNLE", "HND2 Accountancy", "FPO-20260602-19AF", "SUCCESS", "NGN 70,000"],
    ["RABIU HALIMAT", "ND2 Statistics", "FPO-20260602-6B12", "SUCCESS", "NGN 25,000"],
    ["FASASI TAIWO", "HND1 Mass Communication", "FPO-20260601-C88E", "SUCCESS", "NGN 85,000"]
  ];

  const studentRows = [
    ["ADEBAYO MARIAM", "APP/2026/0012", "ND1", "Computer Science", "Active"],
    ["OLADIMEJI KUNLE", "FPO/HND/24/0412", "HND2", "Accountancy", "Active"],
    ["RABIU HALIMAT", "APP/2026/0099", "ND2", "Statistics", "Active"],
    ["FASASI TAIWO", "FPO/HND/25/0031", "HND1", "Mass Communication", "Suspended"]
  ];

  function icon(name) {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name] || icons.dashboard}</svg>`;
  }

  function pageTitle(page) {
    const found = nav.find((item) => item[0] === page) || groups.flatMap((group) => group.pages).find((item) => item[0] === page);
    return found ? found[2] : page.split("-").map((part) => part[0].toUpperCase() + part.slice(1)).join(" ");
  }

  function groupIsOpen(group, page) {
    return group.pages.some((item) => item[0] === page);
  }

  function table(headers, rows, actions = "") {
    const head = headers.map((item) => `<th>${item}</th>`).join("");
    const body = rows.map((row) => `<tr>${row.map((item, index) => `<td data-label="${headers[index]}">${index === 0 ? `<strong>${item}</strong>` : item}</td>`).join("")}${actions ? `<td data-label="Actions">${actions}</td>` : ""}</tr>`).join("");
    const cards = rows.map((row) => `<article class="mobile-card"><h3>${row[0]}</h3>${row.slice(1).map((item, index) => `<div class="mobile-row"><span>${headers[index + 1]}</span><span>${item}</span></div>`).join("")}${actions ? `<div class="mobile-row"><span>Actions</span><span>${actions}</span></div>` : ""}</article>`).join("");
    return `<div class="table-wrap"><table class="data-table"><thead><tr>${head}${actions ? "<th>Actions</th>" : ""}</tr></thead><tbody>${body}</tbody></table><div class="mobile-cards">${cards}</div></div>`;
  }

  function form(fields, button = "Save Changes") {
    return `<form class="form-grid">${fields.map((field) => `<label class="field${field.wide ? " wide" : ""}">${field.label}${field.type === "textarea" ? `<textarea class="input">${field.value || ""}</textarea>` : field.type === "select" ? `<select class="input">${(field.options || []).map((option) => `<option ${option === field.value ? "selected" : ""}>${option}</option>`).join("")}</select>` : `<input class="input" type="${field.type || "text"}" value="${field.value || ""}" placeholder="${field.placeholder || ""}">`}</label>`).join("")}</form>`;
  }

  function breadcrumbs(page) {
    const title = pageTitle(page);
    const group = groups.find((item) => item.pages.some((child) => child[0] === page));
    return `<nav class="breadcrumbs"><a href="dashboard.html">Dashboard</a><span>/</span>${group ? `<span>${group.label}</span><span>/</span>` : ""}<b>${title}</b></nav>`;
  }

  function filters(items) {
    return `<div class="filter-bar">${items.map((item) => item.type === "select" ? `<select class="input"><option>${item.label}</option>${(item.options || []).map((option) => `<option>${option}</option>`).join("")}</select>` : `<input class="input" type="${item.type || "text"}" placeholder="${item.label}">`).join("")}<button class="btn-secondary" type="button">Apply</button></div>`;
  }

  function bulkBar(label = "3 records selected") {
    return `<div class="bulk-bar"><span>${label}</span><div class="bulk-actions"><button class="btn-secondary" type="button">Export Selected</button><button class="btn-secondary" type="button">Send Reminder</button><button class="btn-danger" data-confirm="Bulk Action" data-confirm-message="This demo action represents applying a bulk operation to selected records." type="button">Bulk Action</button></div></div>`;
  }

  function emptyState(title, message, actionLabel, actionHref) {
    return `<div class="empty-state"><span class="dot">${icon("dashboard")}</span><h3>${title}</h3><p>${message}</p><a class="btn-primary" href="${actionHref}">${actionLabel}</a></div>`;
  }

  function stickyActions(primary = "Save Changes", danger = "") {
    return `<div class="sticky-actions"><div><a class="btn-secondary" href="dashboard.html">Cancel</a></div><div>${danger ? `<button class="btn-danger" data-confirm="Confirm Delete" data-confirm-message="This is a sensitive admin action. Review the record before continuing." type="button">${danger}</button>` : ""}<button class="btn-primary" type="button">${primary}</button></div></div>`;
  }

  function auditPanel() {
    const rows = [
      ["Session activated", "System Administrator opened 2026/2027"],
      ["Fee updated", "Applied Sciences fresher fee changed"],
      ["Student biodata corrected", "Matric number updated by admin"],
      ["Email sent", "Payment reminder delivered to unpaid wallets"]
    ];
    return `<div class="panel section"><h2 class="section-title">Recent Admin Activity</h2><div class="audit-list">${rows.map((row) => `<div class="audit-item"><span class="audit-dot"></span><div><b>${row[0]}</b><span>${row[1]}</span></div></div>`).join("")}</div></div>`;
  }

  function statusBadge(value) {
    return `<span class="badge ${String(value).toLowerCase().includes("active") || String(value).toLowerCase().includes("success") ? "gold" : ""}">${value}</span>`;
  }

  const pages = {
    dashboard: () => `<section class="hero"><div class="panel hero-main"><div><h2>School Fees Collection Control Room</h2><p>Monitor active wallets, session fees, payment confirmations, and administrative actions from one modern interface.</p></div><div class="hero-actions"><a class="btn-secondary" href="payments.html">View Payments</a><a class="btn-primary" href="fees.html">Create Fee</a></div></div><div class="panel session-panel"><h3>Active Session</h3><div class="session-box"><b>2026/2027</b><span class="pill">OPEN</span></div><div class="progress"><div></div></div><p style="color:var(--muted);font-size:12px;font-weight:750">68% of expected wallet targets configured.</p></div></section><section class="metrics"><div class="panel metric"><span>Total Students</span><strong>3,842</strong><small>+214 this session</small></div><div class="panel metric"><span>Wallet Deposits</span><strong>NGN 84.6M</strong><small>Successful payments</small></div><div class="panel metric"><span>Pending Balance</span><strong>NGN 19.2M</strong><small>Across active wallets</small></div><div class="panel metric"><span>Receipts Sent</span><strong>1,286</strong><small>Email delivery active</small></div></section><section class="grid-2"><div class="panel section"><h2 class="section-title">Recent Payment Activity</h2>${table(["Student","Programme","Reference","Status","Amount"], moneyRows)}</div><aside class="panel section"><h2 class="section-title">Quick Operations</h2><div class="quick-list"><a class="quick-item" href="fees.html"><span class="dot">${icon("wallet")}</span><div><b>Configure faculty school fee</b><span>Fresher and staylite categories</span></div></a><a class="quick-item" href="sessions.html"><span class="dot">${icon("calendar")}</span><div><b>Activate next session</b><span>Closes previous open session</span></div></a><a class="quick-item" href="emails.html"><span class="dot">${icon("mail")}</span><div><b>Send payment reminders</b><span>Target unpaid active wallets</span></div></a></div></aside></section>${auditPanel()}`,
    students: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Student Records</h2><p class="section-subtitle">Filter, select, export, or send reminders to student groups.</p></div><a class="btn-primary" href="student-edit.html">Add Student</a></div>${filters([{label:"Search student"},{label:"Session",type:"select",options:["2026/2027","2025/2026"]},{label:"Faculty",type:"select",options:["Applied Sciences","Business and Management"]},{label:"Status",type:"select",options:["Active","Suspended"]}])}${bulkBar()}${table(["Name","Identifier","Level","Department","Status"], studentRows.map((row) => [row[0], row[1], row[2], row[3], statusBadge(row[4])]), '<a class="btn-secondary" href="student-edit.html">Edit</a>')}</section>`,
    "student-edit": () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Student Biodata</h2><p class="section-subtitle">Admin-only correction page with sticky actions.</p></div><span class="badge gold">Active Student</span></div>${form([{label:"Full Name",value:"ADEBAYO MARIAM"},{label:"Email",type:"email",value:"student@fedpoffa.edu.ng"},{label:"Application Number",value:"APP/2026/0012"},{label:"Matric Number",value:""},{label:"Faculty",type:"select",options:["Applied Sciences","Business and Management","Communication and Information"]},{label:"Department",type:"select",options:["Computer Science","Statistics","Accountancy","Mass Communication"]},{label:"Programme",type:"select",options:["ND","HND"]},{label:"Level",type:"select",options:["ND1","ND2","HND1","HND2"]},{label:"Address",wide:true,value:"Offa, Kwara State"}],"Save Student Biodata")}${stickyActions("Save Student Biodata","Suspend Student")}</section>`,
    payments: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Payment Transactions</h2><p class="section-subtitle">Review gateway references, dates, and wallet deposit status.</p></div><button class="btn-secondary" type="button">Export Report</button></div>${filters([{label:"Search reference"},{label:"Date range",type:"date"},{label:"Status",type:"select",options:["Successful","Pending","Failed"]},{label:"Gateway",type:"select",options:["Paystack","Manual"]}])}${table(["Student","Programme","Reference","Status","Amount"], moneyRows.concat([["AJIBOLA KEMI","ND1 Statistics","FPO-20260601-992A","SUCCESS","NGN 15,000"]]).map((row) => [row[0], row[1], row[2], statusBadge(row[3]), row[4]]))}</section>`,
    fees: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Fee Setup</h2><p class="section-subtitle">School fees stay faculty/category based while other fees may target levels.</p></div><a class="btn-primary" href="fee-edit.html">Create Fee</a></div>${filters([{label:"Search fee"},{label:"Fee type",type:"select",options:["School fee","Departmental fee","Acceptance fee"]},{label:"Session",type:"select",options:["2026/2027"]},{label:"Status",type:"select",options:["Active","Draft","Disabled"]}])}${table(["Fee","Scope","Session","Amount","Status"], [["Faculty Fresher School Fee","Applied Sciences / Fresher","2026/2027","NGN 85,000",statusBadge("Active")],["Departmental Practical Fee","Computer Science / ND1","2026/2027","NGN 12,000",statusBadge("Active")],["Acceptance Fee","All faculties / All levels","2026/2027","NGN 25,000",statusBadge("Draft")]], '<a class="btn-secondary" href="fee-edit.html">Edit</a>')}${emptyState("No disabled fees found","When a filter has no result, the interface can show this clear empty state instead of a blank table.","Create Fee","fee-edit.html")}</section>`,
    "fee-edit": () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Create / Edit Fee</h2><p class="section-subtitle">Sticky actions keep saving and delete controls available during long forms.</p></div><span class="badge gold">Active Session Only</span></div>${form([{label:"Fee Title",value:"Faculty Fresher School Fee"},{label:"Fee Type",type:"select",options:["School fee","Departmental fee","Acceptance fee","Institutional charge","Other"]},{label:"Session",type:"select",options:["2026/2027"]},{label:"Amount",type:"number",value:"85000"},{label:"Faculty Scope",type:"select",options:["Applied Sciences","Business and Management","All faculties"]},{label:"Department Scope",type:"select",options:["All departments","Computer Science","Statistics"]},{label:"Programme Type",type:"select",options:["All programmes","ND","HND"]},{label:"Fee Level",type:"select",options:["All levels","ND1","ND2","HND1","HND2"]}],"Save Fee")}${stickyActions("Save Fee","Delete Fee")}</section>`,
    sessions: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Academic Sessions</h2><p class="section-subtitle">Activating a session closes previous open sessions.</p></div><a class="btn-primary" href="session-edit.html">Create Session</a></div>${filters([{label:"Search session"},{label:"Status",type:"select",options:["Open","Closed","Archived"]},{label:"Start date",type:"date"},{label:"End date",type:"date"}])}${table(["Session","Type","Due Date","Status"], [["2026/2027","Current","2027-08-31",statusBadge("Open")],["2025/2026","Closed","2026-08-31",statusBadge("Closed")]], '<a class="btn-secondary" href="session-edit.html">Edit</a>')}</section>`,
    "session-edit": () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Create / Edit Session</h2><p class="section-subtitle">Session activation is a sensitive workflow and should ask for confirmation.</p></div><span class="badge gold">Requires Review</span></div>${form([{label:"Session Name",value:"2026/2027"},{label:"Status",type:"select",options:["Open","Closed","Archived"]},{label:"Start Date",type:"date",value:"2026-09-01"},{label:"End Date",type:"date",value:"2027-08-31"},{label:"Make Active",type:"select",options:["Yes","No"]},{label:"Copy Previous Fees",type:"select",options:["Yes","No"]}],"Save Session")}${stickyActions("Activate Session","Archive Session")}</section>`,
    faculties: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Faculties</h2><p class="section-subtitle">Manage faculty scopes used by school fee rules.</p></div><a class="btn-primary" href="faculty-edit.html">Add Faculty</a></div>${filters([{label:"Search faculty"},{label:"Status",type:"select",options:["Active","Inactive"]},{label:"Departments",type:"select",options:["With departments","No departments"]},{label:"Dean/HOD"}])}${table(["Faculty","Departments","Dean","Status"], [["Applied Sciences","2 departments","Prof. A. Salami",statusBadge("Active")],["Business and Management","1 department","Dr. R. Bello",statusBadge("Active")],["Communication and Information","1 department","Dr. H. Musa",statusBadge("Active")]], '<a class="btn-secondary" href="faculty-edit.html">Edit</a>')}</section>`,
    "faculty-edit": () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Create / Edit Faculty</h2><p class="section-subtitle">Faculty names affect fee scope matching.</p></div><span class="badge gold">Academic Setup</span></div>${form([{label:"Faculty Name",value:"Applied Sciences"},{label:"Dean/HOD",value:"Prof. A. Salami"},{label:"Status",type:"select",options:["Active","Inactive"]}],"Save Faculty")}${stickyActions("Save Faculty","Delete Faculty")}</section>`,
    departments: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Departments</h2><p class="section-subtitle">Department options should be faculty-aware.</p></div><a class="btn-primary" href="department-edit.html">Add Department</a></div>${filters([{label:"Search department"},{label:"Faculty",type:"select",options:["Applied Sciences","Business and Management"]},{label:"Award",type:"select",options:["HND/ND","ND","HND"]},{label:"Status",type:"select",options:["Active","Inactive"]}])}${table(["Department","Faculty","Award","Status"], [["Computer Science","Applied Sciences","HND/ND",statusBadge("Active")],["Statistics","Applied Sciences","ND",statusBadge("Active")],["Accountancy","Business and Management","HND/ND",statusBadge("Active")],["Mass Communication","Communication and Information","HND/ND",statusBadge("Active")]], '<a class="btn-secondary" href="department-edit.html">Edit</a>')}</section>`,
    "department-edit": () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">Create / Edit Department</h2><p class="section-subtitle">Faculty selection controls department grouping.</p></div><span class="badge gold">Academic Setup</span></div>${form([{label:"Department Name",value:"Computer Science"},{label:"Faculty",type:"select",options:["Applied Sciences","Business and Management","Communication and Information"]},{label:"Award Type",type:"select",options:["HND/ND","ND","HND"]},{label:"Status",type:"select",options:["Active","Inactive"]}],"Save Department")}${stickyActions("Save Department","Delete Department")}</section>`,
    emails: () => `<section class="grid-2"><div class="panel section"><div class="section-head"><div><h2 class="section-title">Email Center</h2><p class="section-subtitle">Bulk email actions should be confirmed before sending.</p></div><span class="badge gold">SMTP Active</span></div>${form([{label:"Recipient Group",type:"select",options:["All active students","Unpaid students","Completed students"]},{label:"Subject",value:"Payment Reminder"},{label:"Message",type:"textarea",wide:true,value:"Dear student, kindly review your outstanding school fee balance."}],"Send Email")}${stickyActions("Send Email")}</div><aside class="panel section"><h2 class="section-title">Recent Email Logs</h2>${table(["Recipient","Subject","Status"], [["student@fedpoffa.edu.ng","Payment Receipt",statusBadge("Sent")],["admin@fedpoffa.edu.ng","SMTP Test",statusBadge("Sent")]])}${emptyState("No failed emails","Failed deliveries will appear here for admin follow-up.","Open Settings","settings.html")}</aside></section>`,
    settings: () => `<section class="panel section"><div class="section-head"><div><h2 class="section-title">System Settings</h2><p class="section-subtitle">Production settings should expose only safe operational controls.</p></div><span class="badge gold">Super Admin</span></div>${form([{label:"Portal Name",value:"FEDPOFFA Student Fee Portal"},{label:"Minimum Deposit",type:"number",value:"5000"},{label:"SMTP From",value:"info@fedpoffa.edu.ng"},{label:"Paystack Mode",type:"select",options:["Test","Live"]},{label:"Support Email",value:"support@fedpoffa.edu.ng"},{label:"Receipt Branding",type:"select",options:["Institutional Green","Dark"]}],"Save Settings")}${stickyActions("Save Settings")}</section>`
  };

  function ensureFavicon() {
    if (document.querySelector('link[rel="icon"]')) return;
    const favicon = document.createElement("link");
    favicon.rel = "icon";
    favicon.type = "image/png";
    favicon.sizes = "32x32";
    favicon.href = "../assets/img/favicon-32.png";
    document.head.appendChild(favicon);
  }

  function renderDemo() {
    const host = document.querySelector("#demoApp");
    if (!host) return;
    const page = host.dataset.page || "dashboard";
    const content = pages[page] ? pages[page]() : pages.dashboard();
    host.innerHTML = `<div class="page-progress" aria-hidden="true"></div><div class="admin-shell"><aside class="sidebar"><div class="brand"><a href="dashboard.html" title="Dashboard"><img src="../assets/img/logo2.png" alt="FEDPOFFA"></a><button class="collapse-btn" data-collapse type="button" title="Collapse sidebar">${icon("panel")}</button></div><nav class="nav">${nav.map((item) => `<a class="${item[0] === page ? "active" : ""}" href="${item[3]}" title="${item[2]}"><span class="ico">${icon(item[1])}</span><span>${item[2]}</span></a>`).join("")}${groups.map((group) => `<div class="nav-group ${groupIsOpen(group, page) ? "is-open" : ""}"><button class="group-toggle" data-nav-group type="button" title="${group.label}"><span class="ico">${icon(group.icon)}</span><span>${group.label}</span><span class="chevron">${icon("chevron")}</span></button><div class="group-links">${group.pages.map((item) => `<a class="${item[0] === page ? "active" : ""}" href="${item[3]}" title="${item[2]}"><span class="ico">${icon(item[1])}</span><span>${item[2]}</span></a>`).join("")}</div></div>`).join("")}</nav><div class="footer"><b>System Administrator</b><span class="badge">Super admin</span><a href="dashboard.html" title="Logout"><span class="ico">${icon("logout")}</span><span>Logout</span></a></div></aside><button class="drawer-backdrop" data-drawer-close type="button" aria-label="Close menu"></button><section class="main"><header class="topbar"><div class="title"><button class="mobile-menu" data-drawer-open type="button" title="Open sidebar">${icon("menu")}</button><div><h1>${pageTitle(page)}</h1><p>Modern admin demo for FEDPOFFA payments portal.</p></div></div><div class="actions"><input class="search" placeholder="Search student, payment reference..."><button class="icon-btn" data-theme-toggle type="button"></button><div class="profile"><span class="badge gold">Super Admin</span> SA System Admin</div></div></header><main class="content">${breadcrumbs(page)}${content}</main></section></div><div class="confirm-backdrop" data-confirm-modal><div class="confirm-modal"><h3 data-confirm-title>Confirm Action</h3><p data-confirm-copy>This action needs approval before it continues.</p><div class="confirm-actions"><button class="btn-secondary" data-confirm-close type="button">Cancel</button><button class="btn-danger" data-confirm-close type="button">Continue</button></div></div></div>`;
  }

  const root = document.documentElement;
  const requestedTheme = new URLSearchParams(window.location.search).get("theme");
  const savedTheme = requestedTheme || localStorage.getItem("fedpoffa_demo_theme");
  root.classList.toggle("dark", savedTheme ? savedTheme === "dark" : false);

  function syncThemeButtons() {
    document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
      button.innerHTML = root.classList.contains("dark") ? icon("sun") : icon("moon");
      button.title = root.classList.contains("dark") ? "Switch to light mode" : "Switch to dark mode";
    });
  }

  function startLoading() {
    document.body.classList.add("is-loading");
  }

  function stopLoading() {
    document.body.classList.remove("is-loading");
  }

  function markButtonBusy(button, label = "Working...") {
    if (!button || button.classList.contains("is-busy")) return;
    button.dataset.originalText = button.textContent.trim();
    button.classList.add("is-busy");
    button.textContent = label;
    window.setTimeout(() => {
      button.classList.remove("is-busy");
      button.textContent = button.dataset.originalText || "Save";
      delete button.dataset.originalText;
    }, 900);
  }

  function openConfirm(button) {
    const modal = document.querySelector("[data-confirm-modal]");
    if (!modal) return;
    modal.querySelector("[data-confirm-title]").textContent = button.dataset.confirm || "Confirm Action";
    modal.querySelector("[data-confirm-copy]").textContent = button.dataset.confirmMessage || "This action needs approval before it continues.";
    modal.classList.add("is-open");
  }

  function closeConfirm() {
    document.querySelector("[data-confirm-modal]")?.classList.remove("is-open");
  }

  document.addEventListener("click", (event) => {
    if (event.target.closest("[data-theme-toggle]")) {
      root.classList.toggle("dark");
      localStorage.setItem("fedpoffa_demo_theme", root.classList.contains("dark") ? "dark" : "light");
      syncThemeButtons();
    }
    if (event.target.closest("[data-collapse]")) {
      document.body.classList.toggle("is-collapsed");
    }
    if (event.target.closest("[data-drawer-open]")) {
      document.body.classList.add("drawer-open");
    }
    if (event.target.closest("[data-drawer-close]")) {
      document.body.classList.remove("drawer-open");
    }
    const groupButton = event.target.closest("[data-nav-group]");
    if (groupButton) {
      groupButton.closest(".nav-group")?.classList.toggle("is-open");
    }
    const confirmButton = event.target.closest("[data-confirm]");
    if (confirmButton) {
      openConfirm(confirmButton);
      return;
    }
    if (event.target.closest("[data-confirm-close]") || event.target.matches("[data-confirm-modal]")) {
      closeConfirm();
      return;
    }
    const actionButton = event.target.closest('button.btn-primary, button.btn-secondary, button.btn-danger');
    if (actionButton) {
      markButtonBusy(actionButton);
    }
    const link = event.target.closest('a[href$=".html"]');
    if (link) {
      startLoading();
    }
  });

  ensureFavicon();
  renderDemo();
  syncThemeButtons();
  window.setTimeout(stopLoading, 280);
  window.addEventListener("beforeunload", startLoading);
})();
