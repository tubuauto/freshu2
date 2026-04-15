const state = {
  token: localStorage.getItem("fresh2u_token") || "",
  tenant: localStorage.getItem("fresh2u_tenant") || "",
};

function saveAuth(token, tenant) {
  state.token = token || state.token;
  state.tenant = tenant || state.tenant;
  localStorage.setItem("fresh2u_token", state.token || "");
  localStorage.setItem("fresh2u_tenant", state.tenant || "");
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

function print(elId, value) {
  const el = document.getElementById(elId);
  if (!el) return;
  el.textContent = typeof value === "string" ? value : JSON.stringify(value, null, 2);
}

function bindAuthPanel() {
  const loginBtn = document.getElementById("loginBtn");
  const email = document.getElementById("authEmail");
  const password = document.getElementById("authPassword");
  const tenant = document.getElementById("authTenant");
  const out = document.getElementById("authOut");

  if (tenant && state.tenant) tenant.value = state.tenant;

  if (!loginBtn) return;

  loginBtn.addEventListener("click", async () => {
    try {
      saveAuth(state.token, tenant?.value || "");
      const result = await api("/api/v1/auth/login", "POST", {
        email: email?.value,
        password: password?.value,
      });
      saveAuth(result.data.token, tenant?.value || result.data.user.tenant_merchant_id || "");
      out.textContent = JSON.stringify(result, null, 2);
    } catch (err) {
      out.textContent = err.message;
      out.classList.add("error");
    }
  });
}

window.Fresh2U = {
  state,
  saveAuth,
  api,
  print,
  bindAuthPanel,
};
