<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Administración de Tenants — Clínica</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<style>
		:root {
			--primary: #1e3a5f;
			--primary-light: #2c5282;
			--accent: #38a169;
			--danger: #e53e3e;
			--bg: #f0f4f8;
		}
		body { background: var(--bg); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; }
		.card { border: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
		.btn-primary { background: var(--primary); border: none; }
		.btn-primary:hover { background: var(--primary-light); }
		.badge-estado { font-size: 0.75rem; text-transform: capitalize; }
		#loginSection { max-width: 400px; margin: 0 auto; padding-top: 10vh; }
		#appSection { display: none; padding: 1.5rem 0; }
		.nav-admin {
			background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
			color: #fff; padding: 0.85rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;
		}
		.table th { font-weight: 600; color: #4a5568; font-size: 0.85rem; }
		.modal-content { border: none; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
		.form-control, .form-select { border-radius: 8px; border: 1px solid #e2e8f0; }
		.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,58,95,.15); }
		.kpi-card { border-left: 4px solid; border-radius: 10px; }
		.kpi-card .kpi-value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
		.kpi-card .kpi-label { font-size: 0.8rem; color: #64748b; }
		.acciones-tenant { display: flex; flex-wrap: wrap; gap: 4px; }
		.acciones-tenant .btn { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
		.toast-wrap { position: fixed; top: 16px; right: 16px; z-index: 9999; min-width: 280px; }
		.filter-bar .btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
		.db-master-hint { font-size: 0.8rem; opacity: 0.85; }
	</style>
</head>
<body>
	<div class="toast-wrap" id="toastWrap"></div>

	<!-- Login -->
	<div id="loginSection">
		<div class="card shadow-sm p-4">
			<div class="text-center mb-4">
				<div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:56px;height:56px;">
					<i class="bi bi-shield-lock text-primary" style="font-size:1.5rem;"></i>
				</div>
				<h5 class="mb-1">Administración de Tenants</h5>
				<p class="text-muted small mb-0">BD maestra <code>clinica</code> · Activar / desactivar clínicas</p>
			</div>
			<form id="formLogin">
				<div class="mb-3">
					<label class="form-label">Usuario</label>
					<input type="text" class="form-control" id="loginUsuario" required placeholder="Usuario administrador" autocomplete="username">
				</div>
				<div class="mb-3">
					<label class="form-label">Contraseña</label>
					<input type="password" class="form-control" id="loginClave" required placeholder="Contraseña" autocomplete="current-password">
				</div>
				<div id="loginError" class="alert alert-danger py-2 small d-none"></div>
				<button type="submit" class="btn btn-primary w-100">Entrar</button>
			</form>
		</div>
	</div>

	<!-- App -->
	<div id="appSection">
		<div class="container-fluid px-3 px-md-4">
			<div class="nav-admin d-flex justify-content-between align-items-center flex-wrap gap-2">
				<div>
					<div class="d-flex align-items-center gap-2">
						<i class="bi bi-building"></i>
						<strong>Administración de Tenants</strong>
					</div>
					<div class="db-master-hint mt-1">
						<i class="bi bi-database"></i> Control central sobre BD maestra <code class="text-white">clinica</code>
					</div>
				</div>
				<div class="d-flex align-items-center gap-2">
					<span class="small opacity-90" id="adminNombre"></span>
					<button type="button" class="btn btn-sm btn-light" id="btnLogout">Cerrar sesión</button>
				</div>
			</div>

			<!-- KPIs -->
			<div class="row g-3 mb-3" id="kpiRow">
				<div class="col-6 col-md-3">
					<div class="card kpi-card p-3" style="border-left-color:#38a169;">
						<div class="kpi-label">Activos</div>
						<div class="kpi-value text-success" id="kpiActivos">0</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card kpi-card p-3" style="border-left-color:#718096;">
						<div class="kpi-label">Inactivos</div>
						<div class="kpi-value text-secondary" id="kpiInactivos">0</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card kpi-card p-3" style="border-left-color:#e53e3e;">
						<div class="kpi-label">Bloqueados</div>
						<div class="kpi-value text-danger" id="kpiBloqueados">0</div>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="card kpi-card p-3" style="border-left-color:#1e3a5f;">
						<div class="kpi-label">Total</div>
						<div class="kpi-value" id="kpiTotal">0</div>
					</div>
				</div>
			</div>

			<div class="card p-3 p-md-4">
				<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
					<div>
						<h5 class="mb-0">Listado de tenants</h5>
						<small class="text-muted">Active o desactive clínicas con un clic</small>
					</div>
					<div class="d-flex gap-2 flex-wrap">
						<button type="button" class="btn btn-outline-warning btn-sm" id="btnSyncAll" title="Agregar columnas/tablas faltantes a todos los tenants activos">
							<i class="bi bi-database-gear"></i> Sync esquemas
						</button>
						<button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefresh">
							<i class="bi bi-arrow-clockwise"></i> Actualizar
						</button>
						<button type="button" class="btn btn-primary" id="btnNuevo">
							<i class="bi bi-plus-lg me-1"></i>Nuevo tenant
						</button>
					</div>
				</div>

				<div class="row g-2 mb-3 filter-bar align-items-end">
					<div class="col-md-4">
						<input type="search" class="form-control" id="filtroTexto" placeholder="Buscar nombre, subdominio o BD...">
					</div>
					<div class="col-md-8 d-flex flex-wrap gap-1">
						<button type="button" class="btn btn-sm btn-outline-secondary active" data-filtro="todos">Todos</button>
						<button type="button" class="btn btn-sm btn-outline-success" data-filtro="activo">Activos</button>
						<button type="button" class="btn btn-sm btn-outline-secondary" data-filtro="inactivo">Inactivos</button>
						<button type="button" class="btn btn-sm btn-outline-danger" data-filtro="bloqueado">Bloqueados</button>
						<button type="button" class="btn btn-sm btn-outline-warning" data-filtro="vencido">Vencidos</button>
						<button type="button" class="btn btn-sm btn-outline-warning" data-filtro="por_vencer">Por vencer</button>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table table-hover align-middle mb-0">
						<thead>
							<tr>
								<th>Nombre</th>
								<th>Dominio / Subdominio</th>
								<th>BD / DocRoot</th>
								<th>Estado</th>
								<th>Activo</th>
								<th>Vhost</th>
								<th style="min-width:280px;">Acciones</th>
							</tr>
						</thead>
						<tbody id="tbodyTenants"></tbody>
					</table>
				</div>
				<div id="loadingTenants" class="text-center py-4 text-muted">
					<div class="spinner-border spinner-border-sm me-2" role="status"></div>Cargando...
				</div>
				<div id="emptyTenants" class="text-center py-4 text-muted d-none">No hay tenants con ese filtro.</div>
			</div>
		</div>
	</div>

	<!-- Modal crear/editar -->
	<div class="modal fade" id="modalTenant" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title" id="modalTenantTitle">Nuevo tenant</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<form id="formTenant">
						<input type="hidden" id="tenantId">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">Nombre <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="tenantNombre" required maxlength="255">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label">Subdominio <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="tenantSubdominio" required maxlength="100" placeholder="ej: noe">
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">Nombre de base de datos <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="tenantDatabase" required maxlength="255" placeholder="ej: tenant_felix">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label">Fecha de vencimiento</label>
								<input type="date" class="form-control" id="tenantVencimiento">
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">Dominio (FQDN)</label>
								<input type="text" class="form-control" id="tenantDominio" maxlength="255" placeholder="ej: isalex.odontoed.com">
								<small class="text-muted">Si vacío: subdominio.odontoed.com</small>
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label">API URL</label>
								<input type="url" class="form-control" id="tenantApiUrl" maxlength="500" placeholder="https://isalex.odontoed.com">
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label">Document root (Apache)</label>
							<input type="text" class="form-control" id="tenantDocRoot" maxlength="500" placeholder="/var/www/isalex.odontoed.com/public_html">
							<small class="text-muted">Debe estar bajo /var/www/</small>
						</div>
						<div class="row">
							<div class="col-6 mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="tenantActivo" checked>
									<label class="form-check-label" for="tenantActivo">Activo (puede entrar)</label>
								</div>
							</div>
							<div class="col-6 mb-3">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="tenantBloqueado">
									<label class="form-check-label" for="tenantBloqueado">Bloqueado</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4 mb-3">
								<label class="form-label">Contacto (nombre)</label>
								<input type="text" class="form-control" id="tenantContactoNombre" maxlength="255">
							</div>
							<div class="col-md-4 mb-3">
								<label class="form-label">Contacto (email)</label>
								<input type="email" class="form-control" id="tenantContactoEmail" maxlength="255">
							</div>
							<div class="col-md-4 mb-3">
								<label class="form-label">Contacto (teléfono)</label>
								<input type="text" class="form-control" id="tenantContactoTelefono" maxlength="50">
							</div>
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

	<!-- Modal subir frontend -->
	<div class="modal fade" id="modalDeploy" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title">Subir frontend (React build)</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" id="deployTenantId">
					<p class="small text-muted mb-2" id="deployTenantInfo"></p>
					<ol class="small">
						<li>En el proyecto React: <code>npm run build</code></li>
						<li>Comprima el <strong>contenido</strong> de <code>build/</code> en un ZIP (index.html + static/)</li>
						<li>Súbalo aquí → se despliega en el document_root del tenant</li>
					</ol>
					<input type="file" class="form-control" id="deployZip" accept=".zip,application/zip">
					<div class="progress mt-3 d-none" id="deployProgress">
						<div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%">Subiendo...</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
					<button type="button" class="btn btn-primary" id="btnDeployUpload"><i class="bi bi-cloud-upload"></i> Subir y desplegar</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const API = '';
		let token = localStorage.getItem('admin_token');
		let modalTenant;
		let tenantsCache = [];
		let filtroEstado = 'todos';
		let filtroTexto = '';

		function getAuthHeaders() {
			return {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'Authorization': 'Bearer ' + token
			};
		}

		function toast(msg, type) {
			const wrap = document.getElementById('toastWrap');
			const el = document.createElement('div');
			el.className = 'alert alert-' + (type || 'success') + ' shadow-sm py-2 mb-2';
			el.textContent = msg;
			wrap.appendChild(el);
			setTimeout(() => el.remove(), 3200);
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

		function escapeHtml(s) {
			if (s == null) return '';
			const div = document.createElement('div');
			div.textContent = s;
			return div.innerHTML;
		}

		function estadoTenant(t) {
			return t.estado || (t.bloqueado ? 'bloqueado' : (!t.activo ? 'inactivo' : 'activo'));
		}

		function badgeClass(estado) {
			return {
				activo: 'bg-success',
				inactivo: 'bg-secondary',
				bloqueado: 'bg-danger',
				vencido: 'bg-danger',
				por_vencer: 'bg-warning text-dark'
			}[estado] || 'bg-secondary';
		}

		function actualizarKpis(list) {
			document.getElementById('kpiTotal').textContent = list.length;
			document.getElementById('kpiActivos').textContent = list.filter(t => estadoTenant(t) === 'activo').length;
			document.getElementById('kpiInactivos').textContent = list.filter(t => !t.activo).length;
			document.getElementById('kpiBloqueados').textContent = list.filter(t => !!t.bloqueado).length;
		}

		function tenantsFiltrados() {
			const q = filtroTexto.trim().toLowerCase();
			return tenantsCache.filter(t => {
				const est = estadoTenant(t);
				if (filtroEstado !== 'todos' && est !== filtroEstado) return false;
				if (!q) return true;
				const blob = [t.nombre, t.subdominio, t.database_name, t.contacto_nombre, t.contacto_email].join(' ').toLowerCase();
				return blob.includes(q);
			});
		}

		function renderTabla() {
			const tbody = document.getElementById('tbodyTenants');
			const empty = document.getElementById('emptyTenants');
			const list = tenantsFiltrados();
			tbody.innerHTML = '';

			if (!list.length) {
				empty.classList.remove('d-none');
				return;
			}
			empty.classList.add('d-none');

			list.forEach(t => {
				const estado = estadoTenant(t);
				const dominio = t.dominio || (t.subdominio ? t.subdominio + '.odontoed.com' : '—');
				const docroot = t.document_root || ('/var/www/' + dominio + '/public_html');
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>
						<strong>${escapeHtml(t.nombre)}</strong>
						${t.puede_acceder ? '<div class="small text-success">Puede acceder</div>' : '<div class="small text-danger">Sin acceso</div>'}
						${t.ultimo_deploy_at ? '<div class="small text-muted">Deploy: ' + new Date(t.ultimo_deploy_at).toLocaleString('es') + '</div>' : ''}
					</td>
					<td>
						<code>${escapeHtml(dominio)}</code>
						<div class="small text-muted">sub: ${escapeHtml(t.subdominio)}</div>
					</td>
					<td>
						<small><strong>${escapeHtml(t.database_name)}</strong></small>
						<div class="small text-muted" style="max-width:220px;word-break:break-all;">${escapeHtml(docroot)}</div>
					</td>
					<td><span class="badge ${badgeClass(estado)} badge-estado">${escapeHtml(estado)}</span></td>
					<td>
						<span class="badge ${t.activo ? 'bg-success' : 'bg-secondary'}">${t.activo ? 'Sí' : 'No'}</span>
						${t.bloqueado ? '<span class="badge bg-danger ms-1">Bloq.</span>' : ''}
					</td>
					<td>
						<span class="badge ${t.vhost_enabled ? 'bg-success' : 'bg-secondary'}">${t.vhost_enabled ? 'Sí' : 'No'}</span>
					</td>
					<td>
						<div class="acciones-tenant">
							${t.activo
								? `<button type="button" class="btn btn-outline-warning btn-desactivar" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}"><i class="bi bi-pause-circle"></i></button>`
								: `<button type="button" class="btn btn-success btn-activar" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}"><i class="bi bi-play-circle"></i></button>`
							}
							${t.bloqueado
								? `<button type="button" class="btn btn-outline-success btn-desbloquear" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}"><i class="bi bi-unlock"></i></button>`
								: `<button type="button" class="btn btn-outline-danger btn-bloquear" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}"><i class="bi bi-lock"></i></button>`
							}
							<button type="button" class="btn btn-outline-info btn-vhost" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}" title="Crear Apache vhost"><i class="bi bi-hdd-network"></i></button>
							<button type="button" class="btn btn-outline-primary btn-deploy" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}" data-docroot="${escapeHtml(docroot)}" title="Subir React build"><i class="bi bi-cloud-upload"></i></button>
							<button type="button" class="btn btn-outline-warning btn-sync-one" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}" title="Sync esquema BD"><i class="bi bi-database-up"></i></button>
							<button type="button" class="btn btn-outline-secondary btn-editar" data-id="${t.id}"><i class="bi bi-pencil"></i></button>
							<button type="button" class="btn btn-outline-secondary btn-eliminar" data-id="${t.id}" data-nombre="${escapeHtml(t.nombre)}"><i class="bi bi-trash"></i></button>
						</div>
					</td>`;
				tbody.appendChild(tr);
			});

			document.querySelectorAll('.btn-activar').forEach(btn =>
				btn.addEventListener('click', () => cambiarEstado(btn.dataset.id, 'activar', btn.dataset.nombre)));
			document.querySelectorAll('.btn-desactivar').forEach(btn =>
				btn.addEventListener('click', () => cambiarEstado(btn.dataset.id, 'desactivar', btn.dataset.nombre)));
			document.querySelectorAll('.btn-bloquear').forEach(btn =>
				btn.addEventListener('click', () => cambiarEstado(btn.dataset.id, 'bloquear', btn.dataset.nombre)));
			document.querySelectorAll('.btn-desbloquear').forEach(btn =>
				btn.addEventListener('click', () => cambiarEstado(btn.dataset.id, 'desbloquear', btn.dataset.nombre)));
			document.querySelectorAll('.btn-vhost').forEach(btn =>
				btn.addEventListener('click', () => provisionarVhost(btn.dataset.id, btn.dataset.nombre)));
			document.querySelectorAll('.btn-deploy').forEach(btn =>
				btn.addEventListener('click', () => abrirDeploy(btn.dataset.id, btn.dataset.nombre, btn.dataset.docroot)));
			document.querySelectorAll('.btn-sync-one').forEach(btn =>
				btn.addEventListener('click', () => syncEsquema([btn.dataset.id], btn.dataset.nombre)));
			document.querySelectorAll('.btn-editar').forEach(btn =>
				btn.addEventListener('click', () => abrirModal(btn.dataset.id)));
			document.querySelectorAll('.btn-eliminar').forEach(btn =>
				btn.addEventListener('click', () => eliminarTenant(btn.dataset.id, btn.dataset.nombre)));
		}

		async function cargarTenants() {
			const loading = document.getElementById('loadingTenants');
			const empty = document.getElementById('emptyTenants');
			document.getElementById('tbodyTenants').innerHTML = '';
			loading.classList.remove('d-none');
			empty.classList.add('d-none');
			try {
				const r = await fetch(API + '/api/tenants', { headers: getAuthHeaders() });
				if (r.status === 401) { showLogin(); return; }
				const list = await r.json();
				loading.classList.add('d-none');
				tenantsCache = Array.isArray(list) ? list : [];
				actualizarKpis(tenantsCache);
				renderTabla();
			} catch (err) {
				loading.classList.add('d-none');
				empty.classList.remove('d-none');
				empty.textContent = 'Error al cargar. Compruebe la conexión y que DB_DATABASE=clinica.';
			}
		}

		async function cambiarEstado(id, accion, nombre) {
			const mensajes = {
				activar: '¿Activar el tenant "' + nombre + '"?\nPodrá acceder al sistema.',
				desactivar: '¿Desactivar el tenant "' + nombre + '"?\nNo podrá acceder hasta que lo reactive.',
				bloquear: '¿Bloquear el tenant "' + nombre + '"?\nAcceso denegado de inmediato.',
				desbloquear: '¿Desbloquear el tenant "' + nombre + '"?'
			};
			if (!confirm(mensajes[accion] || '¿Confirmar?')) return;

			try {
				const r = await fetch(API + '/api/tenants/' + id + '/' + accion, {
					method: 'POST',
					headers: getAuthHeaders()
				});
				const data = await r.json().catch(() => ({}));
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					toast(data.error || 'No se pudo cambiar el estado', 'danger');
					return;
				}
				toast(data.message || 'Estado actualizado', 'success');
				await cargarTenants();
			} catch (err) {
				toast('Error de conexión', 'danger');
			}
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
					errEl.textContent = data.error || 'Error ' + r.status;
					errEl.classList.remove('d-none');
					btn.disabled = false;
					btn.textContent = 'Entrar';
					return;
				}
				if (!data.token) {
					errEl.textContent = 'El servidor no devolvió sesión. Compruebe FIRMA_TOKEN y DB_DATABASE=clinica en .env';
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
				errEl.textContent = 'Error de conexión: ' + (err.message || '');
				errEl.classList.remove('d-none');
			}
			btn.disabled = false;
			btn.textContent = 'Entrar';
		});

		document.getElementById('btnLogout').addEventListener('click', showLogin);
		document.getElementById('btnRefresh').addEventListener('click', cargarTenants);

		document.getElementById('filtroTexto').addEventListener('input', function(e) {
			filtroTexto = e.target.value;
			renderTabla();
		});

		document.querySelectorAll('[data-filtro]').forEach(btn => {
			btn.addEventListener('click', function() {
				document.querySelectorAll('[data-filtro]').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				filtroEstado = btn.dataset.filtro;
				renderTabla();
			});
		});

		modalTenant = new bootstrap.Modal(document.getElementById('modalTenant'));
		const modalDeploy = new bootstrap.Modal(document.getElementById('modalDeploy'));

		function abrirModal(id) {
			document.getElementById('modalTenantTitle').textContent = id ? 'Editar tenant' : 'Nuevo tenant';
			document.getElementById('formTenant').reset();
			document.getElementById('tenantId').value = id || '';
			document.getElementById('tenantActivo').checked = true;
			document.getElementById('tenantBloqueado').checked = false;
			document.getElementById('tenantDominio').value = '';
			document.getElementById('tenantDocRoot').value = '';
			document.getElementById('tenantApiUrl').value = '';
			if (id) {
				fetch(API + '/api/tenants/' + id, { headers: getAuthHeaders() })
					.then(r => r.json())
					.then(t => {
						document.getElementById('tenantNombre').value = t.nombre || '';
						document.getElementById('tenantSubdominio').value = t.subdominio || '';
						document.getElementById('tenantDatabase').value = t.database_name || '';
						document.getElementById('tenantDominio').value = t.dominio || '';
						document.getElementById('tenantDocRoot').value = t.document_root || '';
						document.getElementById('tenantApiUrl').value = t.api_url || '';
						document.getElementById('tenantVencimiento').value = t.fecha_vencimiento ? String(t.fecha_vencimiento).slice(0, 10) : '';
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
		document.getElementById('btnSyncAll').addEventListener('click', () => {
			if (!confirm('¿Sincronizar esquema (tablas/columnas faltantes) en TODOS los tenants activos?\nSe recomienda backup previo.')) return;
			syncEsquema(null, 'todos los activos', true);
		});

		function abrirDeploy(id, nombre, docroot) {
			document.getElementById('deployTenantId').value = id;
			document.getElementById('deployTenantInfo').textContent = nombre + ' → ' + (docroot || '');
			document.getElementById('deployZip').value = '';
			document.getElementById('deployProgress').classList.add('d-none');
			modalDeploy.show();
		}

		document.getElementById('btnDeployUpload').addEventListener('click', async function() {
			const id = document.getElementById('deployTenantId').value;
			const file = document.getElementById('deployZip').files[0];
			if (!file) { toast('Seleccione un ZIP del build', 'warning'); return; }
			const fd = new FormData();
			fd.append('build', file);
			const prog = document.getElementById('deployProgress');
			prog.classList.remove('d-none');
			this.disabled = true;
			try {
				const r = await fetch(API + '/api/tenants/' + id + '/deploy-frontend', {
					method: 'POST',
					headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
					body: fd
				});
				const data = await r.json().catch(() => ({}));
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					toast(data.error || data.message || 'Error al subir', 'danger');
					return;
				}
				toast(data.message || 'Frontend desplegado', 'success');
				modalDeploy.hide();
				cargarTenants();
			} catch (err) {
				toast('Error de conexión', 'danger');
			} finally {
				prog.classList.add('d-none');
				this.disabled = false;
			}
		});

		async function provisionarVhost(id, nombre) {
			if (!confirm('¿Crear/actualizar VirtualHost Apache para "' + nombre + '"?\nEscribe en /etc/apache2/sites-available y recarga Apache.')) return;
			try {
				const r = await fetch(API + '/api/tenants/' + id + '/provision-vhost', {
					method: 'POST',
					headers: getAuthHeaders(),
					body: JSON.stringify({})
				});
				const data = await r.json().catch(() => ({}));
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					const extra = data.comando_sugerido ? '\n\nEjecute en el servidor:\n' + data.comando_sugerido : '';
					alert((data.error || 'Error') + (data.detalle ? '\n' + data.detalle : '') + extra);
					return;
				}
				toast(data.message || 'Vhost creado', 'success');
				cargarTenants();
			} catch (err) {
				toast('Error de conexión', 'danger');
			}
		}

		async function syncEsquema(ids, label, todos) {
			toast('Sincronizando esquema' + (label ? ' (' + label + ')' : '') + '...', 'info');
			try {
				const body = todos ? { todos: true, backup: false } : { ids: ids.map(Number), backup: false };
				const r = await fetch(API + '/api/tenants/schema-sync', {
					method: 'POST',
					headers: getAuthHeaders(),
					body: JSON.stringify(body)
				});
				const data = await r.json().catch(() => ({}));
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					toast(data.error || 'Error en sync', 'danger');
					return;
				}
				toast(data.message || 'Sync OK', data.fail > 0 ? 'warning' : 'success');
				if (data.resultados && data.resultados.length) {
					console.log('schema-sync', data.resultados);
				}
			} catch (err) {
				toast('Error de conexión', 'danger');
			}
		}

		document.getElementById('btnGuardarTenant').addEventListener('click', async function() {
			const id = document.getElementById('tenantId').value;
			const payload = {
				nombre: document.getElementById('tenantNombre').value.trim(),
				subdominio: document.getElementById('tenantSubdominio').value.trim().toLowerCase(),
				database_name: document.getElementById('tenantDatabase').value.trim(),
				dominio: document.getElementById('tenantDominio').value.trim() || null,
				document_root: document.getElementById('tenantDocRoot').value.trim() || null,
				api_url: document.getElementById('tenantApiUrl').value.trim() || null,
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
					toast(data.error || 'Error al guardar', 'danger');
					return;
				}
				modalTenant.hide();
				toast(data.message || 'Guardado', 'success');
				cargarTenants();
			} catch (err) {
				toast('Error de conexión', 'danger');
			}
		});

		async function eliminarTenant(id, nombre) {
			if (!confirm('¿Eliminar el tenant "' + nombre + '"?\nEsta acción no se puede deshacer.')) return;
			try {
				const r = await fetch(API + '/api/tenants/' + id, {
					method: 'DELETE',
					headers: getAuthHeaders()
				});
				if (r.status === 401) { showLogin(); return; }
				if (!r.ok) {
					const data = await r.json();
					toast(data.error || 'Error al eliminar', 'danger');
					return;
				}
				toast('Tenant eliminado', 'success');
				cargarTenants();
			} catch (err) {
				toast('Error de conexión', 'danger');
			}
		}

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
