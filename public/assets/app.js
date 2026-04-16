const Fresh2U = (() => {
  const STATUS_THEME = {
    draft: "neutral",
    submitted: "pending",
    confirmed: "pending",
    paid: "success",
    awaiting_payment: "pending",
    awaiting_review: "pending",
    awaiting_offline_confirmation: "pending",
    pending_review: "warning",
    partially_paid: "warning",
    failed: "error",
    rejected: "error",
    cancelled: "error",
    completed: "success",
    delivered: "success",
    merchant_fulfilled: "warning",
    at_pickup_hub: "pending",
    out_for_delivery: "warning",
    routed_to_supply_partner: "pending",
    in_fulfillment: "warning",
    posted: "success",
    settled: "success",
    approved: "success",
    ready_for_review: "pending",
    processing: "pending",
    active: "success",
    empty: "neutral",
  };

  const STATUS_ENUMS = {
    "member_orders.status": [
      "draft",
      "awaiting_payment",
      "paid",
      "assigned_to_leader_order",
      "merchant_fulfilled",
      "at_pickup_hub",
      "out_for_delivery",
      "delivered",
      "completed",
      "cancelled",
      "refunded",
    ],
    "member_orders.payment_status": [
      "unpaid",
      "partially_paid",
      "paid",
      "failed",
      "partially_refunded",
      "refunded",
    ],
    "member_orders.collection_status": [
      "not_collected",
      "pending_offline_collection",
      "collected_offline",
      "waived",
      "disputed",
    ],
    "leader_orders.status": [
      "draft",
      "submitted",
      "confirmed",
      "paid",
      "routed_to_supply_partner",
      "in_fulfillment",
      "merchant_fulfilled",
      "at_pickup_hub",
      "handed_over",
      "completed",
      "cancelled",
    ],
    "delivery_tasks.status": [
      "pending_assignment",
      "assigned",
      "accepted",
      "picked_up",
      "out_for_delivery",
      "delivered",
      "failed",
      "cancelled",
    ],
    "recharge_orders.status": [
      "initiated",
      "awaiting_payment",
      "awaiting_offline_confirmation",
      "awaiting_review",
      "confirmed",
      "paid",
      "failed",
      "expired",
      "cancelled",
    ],
    "withdrawal_requests.status": [
      "pending_review",
      "approved",
      "rejected",
      "processing",
      "completed",
      "failed",
      "cancelled",
    ],
    "settlements.state": [
      "pending",
      "calculating",
      "ready_for_review",
      "approved",
      "rejected",
      "posted",
      "paid",
      "partially_paid",
      "closed",
      "cancelled",
    ],
  };

  const state = {
    token: localStorage.getItem("fresh2u_token") || "",
    tenant: localStorage.getItem("fresh2u_tenant") || "",
    user: JSON.parse(localStorage.getItem("fresh2u_user") || "null"),
  };

  function saveAuth(token, tenant, user = null) {
    state.token = token || state.token;
    state.tenant = tenant || state.tenant;
    state.user = user || state.user;

    localStorage.setItem("fresh2u_token", state.token || "");
    localStorage.setItem("fresh2u_tenant", state.tenant || "");
    localStorage.setItem("fresh2u_user", JSON.stringify(state.user || null));
  }

  async function api(path, method = "GET", body = null) {
    const headers = {
      "Content-Type": "application/json",
    };

    if (state.token) {
      headers.Authorization = `Bearer ${state.token}`;
    }

    if (state.tenant) {
      headers["X-Tenant-Merchant-Id"] = state.tenant;
    }

    const resp = await fetch(path, {
      method,
      headers,
      body: body ? JSON.stringify(body) : null,
    });

    const data = await resp.json().catch(() => ({}));
    if (!resp.ok) {
      throw new Error(data.error || `HTTP ${resp.status}`);
    }

    return data;
  }

  function print(target, value) {
    const el = typeof target === "string" ? document.querySelector(target) : target;
    if (!el) return;
    el.textContent = typeof value === "string" ? value : JSON.stringify(value, null, 2);
  }

  function bindAuthPanel(options = {}) {
    const email = document.querySelector(options.email || "#authEmail");
    const password = document.querySelector(options.password || "#authPassword");
    const tenant = document.querySelector(options.tenant || "#authTenant");
    const loginBtn = document.querySelector(options.button || "#loginBtn");
    const output = document.querySelector(options.output || "#authOut");

    const tokenField = document.querySelector("[data-auth-token]");
    const roleField = document.querySelector("[data-auth-role]");
    const userField = document.querySelector("[data-auth-user]");

    if (tenant && state.tenant) {
      tenant.value = state.tenant;
    }

    if (roleField && state.user?.role) {
      roleField.textContent = state.user.role;
    }

    if (userField && state.user?.display_name) {
      userField.textContent = state.user.display_name;
    }

    if (tokenField && state.token) {
      tokenField.textContent = `${state.token.slice(0, 18)}...`;
    }

    if (!loginBtn) return;

    loginBtn.addEventListener("click", async () => {
      loginBtn.disabled = true;
      loginBtn.textContent = "登录中...";

      try {
        saveAuth(state.token, tenant?.value || "", state.user || null);

        const result = await api("/api/v1/auth/login", "POST", {
          email: email?.value,
          password: password?.value,
        });

        const user = result?.data?.user || null;
        const token = result?.data?.token || "";
        const tenantId = tenant?.value || user?.tenant_merchant_id || state.tenant;

        saveAuth(token, tenantId, user);

        if (tokenField) tokenField.textContent = `${token.slice(0, 18)}...`;
        if (roleField && user?.role) roleField.textContent = user.role;
        if (userField && user?.display_name) userField.textContent = user.display_name;

        print(output, result);
      } catch (err) {
        print(output, err.message || "登录失败");
      } finally {
        loginBtn.disabled = false;
        loginBtn.textContent = "登录";
      }
    });
  }

  function activateView(view) {
    const navItems = document.querySelectorAll(".side-rail [data-nav-view]");
    const panels = document.querySelectorAll("[data-view]");

    let hasMatch = false;

    navItems.forEach((item) => {
      const active = item.dataset.navView === view;
      item.classList.toggle("is-active", active);
      if (active) hasMatch = true;
    });

    panels.forEach((panel) => {
      panel.classList.toggle("is-active", panel.dataset.view === view);
    });

    if (hasMatch) {
      location.hash = view;
    }
  }

  function initShell(defaultView = "") {
    const jumpers = document.querySelectorAll("[data-nav-view]");
    const navItems = document.querySelectorAll(".side-rail [data-nav-view]");
    if (!jumpers.length) return;

    jumpers.forEach((item) => {
      item.addEventListener("click", () => {
        activateView(item.dataset.navView);
      });
    });

    const hashView = location.hash.replace("#", "").trim();
    const fallback = navItems[0]?.dataset.navView || jumpers[0].dataset.navView;
    const initial = hashView || defaultView || fallback;
    activateView(initial);
  }

  function statusTheme(status) {
    if (!status) return "neutral";
    return STATUS_THEME[String(status).trim()] || "neutral";
  }

  function decorateStatusElements() {
    const items = document.querySelectorAll("[data-status]");
    items.forEach((el) => {
      const value = (el.dataset.status || "").trim();
      const theme = statusTheme(value);
      el.classList.add("chip", theme);
      if (!el.textContent.trim()) {
        el.textContent = value || "unknown";
      }
    });
  }

  function isValidEnum(enumName, value) {
    const values = STATUS_ENUMS[enumName] || [];
    return values.includes(value);
  }

  function listEnum(enumName) {
    return STATUS_ENUMS[enumName] || [];
  }

  function bindApiActions() {
    const actions = document.querySelectorAll("[data-api-action]");
    actions.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const method = btn.dataset.method || "GET";
        let endpoint = btn.dataset.endpoint || "";
        const output = btn.dataset.output || "";
        const inputSel = btn.dataset.input || "";
        const pathSel = btn.dataset.pathInput || "";

        if (pathSel) {
          const pathInput = document.querySelector(pathSel);
          if (pathInput?.value) {
            endpoint = endpoint.replace("{id}", pathInput.value.trim());
          }
        }

        let payload = null;
        if (inputSel) {
          const input = document.querySelector(inputSel);
          if (input?.value?.trim()) {
            try {
              payload = JSON.parse(input.value);
            } catch (err) {
              print(output, "JSON 格式错误，请检查输入");
              return;
            }
          }
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = "处理中...";

        try {
          const data = await api(endpoint, method, payload);
          print(output, data);
        } catch (err) {
          print(output, err.message || "请求失败");
        } finally {
          btn.disabled = false;
          btn.textContent = originalText;
        }
      });
    });
  }

  function mountRolePage(options = {}) {
    bindAuthPanel();
    initShell(options.defaultView || "");
    bindApiActions();
    decorateStatusElements();
  }

  return {
    state,
    saveAuth,
    api,
    print,
    bindAuthPanel,
    bindApiActions,
    initShell,
    statusTheme,
    isValidEnum,
    listEnum,
    decorateStatusElements,
    mountRolePage,
  };
})();

window.Fresh2U = Fresh2U;

document.addEventListener("DOMContentLoaded", () => {
  if (document.body.dataset.rolePage === "true") {
    Fresh2U.mountRolePage({
      defaultView: document.body.dataset.defaultView || "",
    });
  }
});

