<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Administración de Tenants</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<style>
		:root {
			--primary: #1e3a5f;
			--primary-light: #2c5282;
			--accent: #38a169;
			--danger: #e53e3e;
			--bg: #f7fafc;
		}
		body { background: var(--bg); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
		.card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
		.btn-primary { background: var(--primary); border: none; }
		.btn-primary:hover { background: var(--primary-light); }
		.badge-estado { font-size: 0.75rem; }
		#loginSection { max-width: 400px; margin: 0 auto; padding-top: 10vh; }
		#appSection { display: none; padding: 1.5rem 0; }
		.nav-admin { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: #fff; padding: 0.75rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
		.table th { font-weight: 600; color: #4a5568; }
		.modal-content { border: none; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
		.form-control, .form-select { border-radius: 8px; border: 1px solid #e2e8f0; }
		.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,58,95,.15); }
	</style>
</head>
<body>
	<!-- Login -->
	<div id="loginSection">
		<div class="card shadow-sm p-4">
			<div class="text-center mb-4">
				<div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
					<i class="bi bi-shield-lock text-primary" style="font-size:1.5rem;"></i>
				</div>
				<h5 class="mb-1">Administración de Tenants</h5>
				<p class="text-muted small">Inicie sesión con su usuario administrador</p>
			</div>
			<form id="formLogin">
				<div class="mb-3">
					<label class="form-label">Usuario</label>
					<input type="text" class="form-control" id="loginUsuario" required placeholder="Usuario">
				</div>
				<div class="mb-3">
					<label class="form-label">Contraseña</label>
					<input type="password" class="form-control" id="loginClave" required placeholder="Contraseña">
				</div>
				<div id="loginError" class="alert alert-danger py-2 small d-none"></div>
				<button type="submit" class="btn btn-primary w-100">Entrar</button>
			</form>
		</div>
	</div>

	<!-- App (lista y CRUD) -->
	<div id="appSection">
		<div class="container-fluid">
			<div class="nav-admin d-flex justify-content-between align-items-center flex-wrap gap-2">
				<div class="d-flex align-items-center gap-2">
					<i class="bi bi-building"></i>
					<span>Administración de Tenants</span>
				</div>
				<div class="d-flex align-items-center gap-2">
					<span class="small opacity-90" id="adminNombre"></span>
					<button type="button" class="btn btn-sm btn-light" id="btnLogout">Cerrar sesión</button>
				</div>
			</div>

			<div class="card p-3 p-md-4">
				<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
					<h5 class="mb-0">Listado de tenants</h5>
					<button type="button" class="btn btn-primary" id="btnNuevo"><i class="bi bi-plus-lg me-1"></i>Nuevo tenant</button>
				</div>
				<div class="table-responsive">
					<table class="table table-hover align-middle mb-0">
						<thead>
							<tr>
								<th>Nombre</th>
								<th>Subdominio</th>
								<th>Base de datos</th>
								<th>Estado</th>
								<th>Vencimiento</th>
								<th>Contacto</th>
								<th width="120"></th>
							</tr>
						</thead>
						<tbody id="tbodyTenants"></tbody>
					</table>
				</div>
				<div id="loadingTenants" class="text-center py-4 text-muted">
					<div class="spinner-border spinner-border-sm me-2" role="status"></div>Cargando...
				</div>
				<div id="emptyTenants" class="text-center py-4 text-muted d-none">No hay tenants registrados.</div>
			</div>
		</div>
	</div>

	<!-- Modal crear/editar tenant -->
	<div class="modal fade" id="modalTenant" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title" id="modalTenantTitle">Nuevo tenant</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<form id="formTenant">
						<input type="hidden" id="tenantId">
						<div class="mb-3">
							<label class="form-label">Nombre <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="tenantNombre" required maxlength="255">
						</div>
						<div class="mb-3">
							<label class="form-label">Subdominio <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="tenantSubdominio" required maxlength="100" placeholder="ej: clinica1">
						</div>
						<div class="mb-3">
							<label class="form-label">Nombre de base de datos <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="tenantDatabase" required maxlength="255" placeholder="ej: kadosh_clinica1">
						</div>
						<div class="mb-3">
							<label class="form-label">Fecha de vencimiento</label>
							<input type="date" class="form-control" id="tenantVencimiento">
						</div>
						<div class="row">
							<div class="col-6 mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="tenantActivo" checked>
									<label class="form-check-label" for="tenantActivo">Activo</label>
								</div>
							</div>
							<div class="col-6 mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="tenantBloqueado">
									<label class="form-check-label" for="tenantBloqueado">Bloqueado</label>
								</div>
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label">Contacto (nombre)</label>
							<input type="text" class="form-control" id="tenantContactoNombre" maxlength="255">
						</div>
						<div class="mb-3">
							<label class="form-label">Contacto (email)</label>
							<input type="email" class="form-control" id="tenantContactoEmail" maxlength="255">
						</div>
						<div class="mb-3">
							<label class="form-label">Contacto (teléfono)</label>
							<input type="text" class="form-control" id="tenantContactoTelefono" maxlength="50">
						</div>
						<div class="mb-3">
							<label class="form-label">Notas</label>
							<textarea class="form-control" id="tenantNotas" rows="2"></textarea>
						</div>
					</form>
				</div>
				<div class="modal-footer border-0 pt-0">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="button" class="btn btn-primary" id="btnGuardarTenant">Guardar</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const API = '';
		let token = localStorage.getItem('admin_token');
		let modalTenantEl, modalTenant;

		function getAuthHeaders() {
			return {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'Authorization': 'Bearer ' + token
			};
		}

		function showLogin() {
			document.getElementById('loginSection').style.display = 'block';
			document.getElementById('appSection').style.display = 'none';
			localStorage.removeItem('admin_token');
			localStorage.removeItem('admin_nombre');
			token = null;
		}

		function showApp() {
			document.getElementById('loginSection').style.display = 'none';
			document.getElementById('appSection').style.display = 'block';
			document.getElementById('adminNombre').textContent = localStorage.getItem('admin_nombre') || 'Admin';
			cargarTenants();
		}

		// Login
		document.getElementById('formLogin').addEventListener('submit', async function(e) {
			e.preventDefault();
			const errEl = document.getElementById('loginError');
			const btn = e.target.querySelector('button[type="submit"]');
			errEl.classList.add('d-none');
			const usuario = document.getElementById('loginUsuario').value.trim();
			const clave = document.getElementById('loginClave').value;
			btn.disabled = true;
			btn.textContent = 'Entrando...';
			try {
				const r = await fetch(API + '/api/admin/login', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
					body: JSON.stringify({ usuario, clave })
				});
				const contentType = r.headers.get('content-type') || '';
				let data = {};
				if (contentType.includes('application/json')) {
					try { data = await r.json(); } catch (_) { data = { error: 'Respuesta inválida del servidor' }; }
				} else {
					const text = await r.text();
					data = { error: r.ok ? 'Respuesta inesperada' : (text.slice(0, 200) || 'Error ' + r.status) };
				}
				if (!r.ok) {
					errEl.textContent = data.error || (data.errors ? JSON.stringify(data.errors) : 'Error ' + r.status);
					errEl.classList.remove('d-none');
					btn.disabled = false;
					btn.textContent = 'Entrar';
					return;
				}
				if (!data.token) {
					errEl.textContent = 'El servidor no devolvió sesión. Compruebe FIRMA_TOKEN en .env';
					errEl.classList.remove('d-none');
					btn.disabled = false;
					btn.textContent = 'Entrar';
					return;
				}
				token = data.token;
				localStorage.setItem('admin_token', token);
				localStorage.setItem('admin_nombre', [data.nombre, data.apellido].filter(Boolean).join(' '));
				showApp();
			} catch (err) {
				errEl.textContent = 'Error de conexión: ' + (err.message || 'Compruebe la URL y que el servidor esté en marcha.');
				errEl.classList.remove('d-none');
			}
			btn.disabled = false;
			btn.textContent = 'Entrar';
		});

		document.getElementById('btnLogout').addEventListener('click', showLogin);

		// Listar tenants
		async function cargarTenants() {
			const tbody = document.getElementById('tbodyTenants');
			const loading = document.getElementById('loadingTenants');
			const empty = document.getElementById('emptyTenants');
			tbody.innerHTML = '';
			loading.classList.remove('d-none');
			empty.classList.add('d-none');
			try {
				const r = await fetch(API + '/api/tenants', { headers: getAuthHeaders() });
				if (r.status === 401) { showLogin(); return; }
				const list = await r.json();
				loading.classList.add('d-none');
				if (!list.length) { empty.classList.remove('d-none'); return; }
				list.forEach(t => {
					const tr = document.createElement('tr');
					const venc = t.fecha_vencimiento ? new Date(t.fecha_vencimiento).toLocaleDateString('es') : '—';
					const estado = t.estado || (t.activo && !t.bloqueado ? 'activo' : t.bloqueado ? 'bloqueado' : 'inactivo');
					const badgeClass = { activo: 'bg-success', inactivo: 'bg-secondary', bloqueado: 'bg-danger', vencido: 'bg-danger', por_vencer: 'bg-warning text-dark' }[estado] || 'bg-secondary';
					const contacto = [t.contacto_nombre, t.contacto_email].filter(Boolean).join(' · ') || '—';
					tr.innerHTML = `
						<td><strong>${escapeHtml(t.nombre)}</strong></td>
						<td><code>${escapeHtml(t.subdominio)}</code></td>
						<td><small class="text-muted">${escapeHtml(t.database_name)}</small></td>
						<td><span class="badge ${badgeClass} badge-estado">${estado}</span></td>
						<td>${venc}</td>
						<td><small>${escapeHtml(contacto)}</small></td>
						<td>
							<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-editar" data-id="${t.id}">Editar</button>
							<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}">Eliminar</button>
						</td>`;
					tbody.appendChild(tr);
				});
				document.querySelectorAll('.btn-editar').forEach(btn => btn.addEventListener('click', () => abrirModal(btn.dataset.id)));
				document.querySelectorAll('.btn-eliminar').forEach(btn => btn.addEventListener('click', () => eliminarTenant(btn.dataset.id, btn.dataset.nombre)));
			} catch (err) {
				loading.classList.add('d-none');
				empty.classList.remove('d-none');
				empty.textContent = 'Error al cargar. Compruebe la conexión.';
			}
		}

		function escapeHtml(s) {
			if (s == null) return '';
			const div = document.createElement('div');
			div.textContent = s;
			return div.innerHTML;
		}

		// Modal crear/editar
		modalTenantEl = document.getElementById('modalTenant');
		modalTenant = new bootstrap.Modal(modalTenantEl);

		function abrirModal(id) {
			document.getElementById('modalTenantTitle').textContent = id ? 'Editar tenant' : 'Nuevo tenant';
			document.getElementById('formTenant').reset();
			document.getElementById('tenantId').value = id || '';
			document.getElementById('tenantActivo').checked = true;
			document.getElementById('tenantBloqueado').checked = false;
			if (id) {
				fetch(API + '/api/tenants/' + id, { headers: getAuthHeaders() })
					.then(r => r.json())
					.then(t => {
						document.getElementById('tenantNombre').value = t.nombre || '';
						document.getElementById('tenantSubdominio').value = t.subdominio || '';
						document.getElementById('tenantDatabase').value = t.database_name || '';
						document.getElementById('tenantVencimiento').value = t.fecha_vencimiento ? t.fecha_vencimiento.slice(0, 10) : '';
						document.getElementById('tenantActivo').checked = t.activo !== false;
						document.getElementById('tenantBloqueado').checked = !!t.bloqueado;
						document.getElementById('tenantContactoNombre').value = t.contacto_nombre || '';
						document.getElementById('tenantContactoEmail').value = t.contacto_email || '';
						document.getElementById('tenantContactoTelefono').value = t.contacto_telefono || '';
						document.getElementById('tenantNotas').value = t.notas || '';
					});
			}
			modalTenant.show();
		}

		document.getElementById('btnNuevo').addEventListener('click', () => abrirModal(null));

		document.getElementById('btnGuardarTenant').addEventListener('click', async function() {
			const id = document.getElementById('tenantId').value;
			const payload = {
				nombre: document.getElementById('tenantNombre').value.trim(),
				subdominio: document.getElementById('tenantSubdominio').value.trim().toLowerCase(),
				database_name: document.getElementById('tenantDatabase').value.trim(),
				fecha_vencimiento: document.getElementById('tenantVencimiento').value || null,
				activo: document.getElementById('tenantActivo').checked,
				bloqueado: document.getElementById('tenantBloqueado').checked,
				contacto_nombre: document.getElementById('tenantContactoNombre').value.trim() || null,
				contacto_email: document.getElementById('tenantContactoEmail').value.trim() || null,
				contacto_telefono: document.getElementById('tenantContactoTelefono').value.trim() || null,
				notas: document.getElementById('tenantNotas').value.trim() || null
			};
			const url = id ? (API + '/api/tenants/' + id) : (API + '/api/tenants');
			const method = id ? 'PUT' : 'POST';
			try {
				const r = await fetch(url, {
					method,
					headers: getAuthHeaders(),
					body: JSON.stringify(payload)
				});
				const data = await r.json();
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					alert(data.error || (data.errors ? JSON.stringify(data.errors) : 'Error al guardar'));
					return;
				}
				modalTenant.hide();
				cargarTenants();
			} catch (err) {
				alert('Error de conexión');
			}
		});

		async function eliminarTenant(id, nombre) {
			if (!confirm('¿Eliminar el tenant “‘ + nombre +’”?\nEsta acción no se puede deshacer.')) return;
			try {
				const r = await fetch(API + '/api/tenants/' + id, {
					method: 'DELETE',
					headers: getAuthHeaders()
				});
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					const data = await r.json();
					alert(data.error || 'Error al eliminar');
					return;
				}
				cargarTenants();
			} catch (err) {
				alert('Error de conexión');
			}
		}

		// Inicio
		if (token) {
			fetch(API + '/api/admin/me', { headers: getAuthHeaders() })
				.then(r => { if (r.ok) showApp(); else showLogin(); })
				.catch(() => showLogin());
		} else {
			showLogin();
		}
	</script>
</body>
</html>
