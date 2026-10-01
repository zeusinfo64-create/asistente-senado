// Catálogo de navegación institucional (Fase 7.3).
//
// Este archivo es la UNICA fuente declarativa de verdad para:
//   - qué opciones de navegación existen;
//   - su grupo, etiqueta, ruta e icono;
//   - qué roles están autorizados en cada opción;
//   - si la opción está operativa en esta fase.
//
// No se declaran rutas ni reglas de roles en ningún otro archivo:
// Sidebar y RoleRoute consumen este catálogo mediante sus funciones auxiliares.

import { ROLES } from "../../utils/roles";

export { ROLES };

const MENU_GROUPS = [
  {
    key: "dashboard",
    label: "Dashboard",
    icon: "flaticon-025-dashboard",
    items: [
      {
        key: "dashboard",
        label: "Dashboard",
        path: "/dashboard",
        icon: "flaticon-025-dashboard",
        roles: [ROLES.ADMIN, ROLES.TECH, ROLES.REQUESTER],
        enabled: true,
        pendingReason: null,
      },
    ],
  },

  {
    key: "requester",
    label: "Solicitudes",
    icon: "flaticon-050-info",
    items: [
      {
        key: "solicitar-soporte",
        label: "Solicitar Soporte",
        path: "/solicitar-soporte",
        icon: "flaticon-050-info",
        roles: [ROLES.REQUESTER],
        enabled: false,
        pendingReason: "Interfaz de registro de solicitudes: pendiente de fase posterior.",
      },
      {
        key: "mis-solicitudes",
        label: "Mis Tickets",
        path: "/mis-solicitudes",
        icon: "flaticon-025-dashboard",
        roles: [ROLES.REQUESTER],
        enabled: false,
        pendingReason: "Consulta de solicitudes propias: pendiente de fase posterior.",
      },
    ],
  },

  {
    key: "technician",
    label: "Técnico",
    icon: "flaticon-072-printer",
    items: [
      {
        key: "mis-tickets-asignados",
        label: "Mis Tickets",
        path: "/mis-tickets-asignados",
        icon: "flaticon-025-dashboard",
        roles: [ROLES.TECH],
        enabled: false,
        pendingReason: "Consulta de tickets asignados: pendiente de fase posterior.",
      },
      {
        key: "atencion-tecnica",
        label: "Atención Técnica",
        path: "/atencion-tecnica",
        icon: "flaticon-072-printer",
        roles: [ROLES.TECH],
        enabled: false,
        pendingReason: "Interfaz de atención técnica: pendiente de fase posterior.",
      },
      {
        key: "diagnostico",
        label: "Diagnóstico",
        path: "/diagnostico",
        icon: "flaticon-043-menu",
        roles: [ROLES.TECH],
        enabled: false,
        pendingReason: "Interfaz de diagnóstico guiado: pendiente de fase posterior.",
      },
      {
        key: "intervenciones",
        label: "Intervenciones",
        path: "/intervenciones",
        icon: "flaticon-072-printer",
        roles: [ROLES.TECH],
        enabled: false,
        pendingReason: "Registro de intervenciones: pendiente de fase posterior.",
      },
      {
        key: "historial-tecnico",
        label: "Historial Técnico",
        path: "/historial-tecnico",
        icon: "flaticon-025-dashboard",
        roles: [ROLES.TECH],
        enabled: false,
        pendingReason: "Historial de atenciones: pendiente de fase posterior.",
      },
    ],
  },

  {
    key: "admin",
    label: "Administración",
    icon: "flaticon-087-stop",
    items: [
      {
        key: "tickets",
        label: "Tickets",
        path: "/tickets",
        icon: "flaticon-025-dashboard",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Gestión de tickets: pendiente de fase posterior.",
      },
      {
        key: "usuarios",
        label: "Usuarios",
        path: "/usuarios",
        icon: "flaticon-065-icon",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Gestión de usuarios: pendiente de fase posterior.",
      },
      {
        key: "tecnicos",
        label: "Técnicos",
        path: "/tecnicos",
        icon: "flaticon-072-printer",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Gestión de técnicos: pendiente de fase posterior.",
      },
      {
        key: "unidades-organizacionales",
        label: "Unidades Organizacionales",
        path: "/unidades-organizacionales",
        icon: "flaticon-043-menu",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Consulta de unidades organizacionales: pendiente de fase posterior.",
      },
      {
        key: "categorias",
        label: "Categorías",
        path: "/categorias",
        icon: "flaticon-043-menu",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Consulta de categorías: pendiente de fase posterior.",
      },
      {
        key: "activos",
        label: "Activos",
        path: "/activos",
        icon: "flaticon-043-menu",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Registro de activos informáticos: pendiente de fase posterior.",
      },
      {
        key: "reportes",
        label: "Reportes",
        path: "/reportes",
        icon: "flaticon-041-graph",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Consolidación de reportes: pendiente de fase posterior.",
      },
      {
        key: "configuracion",
        label: "Configuración",
        path: "/configuracion",
        icon: "flaticon-087-stop",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Parámetros del sistema: pendiente de fase posterior.",
      },
      {
        key: "auditoria",
        label: "Auditoría",
        path: "/auditoria",
        icon: "flaticon-041-graph",
        roles: [ROLES.ADMIN],
        enabled: false,
        pendingReason: "Consulta de auditoría: pendiente de fase posterior.",
      },
    ],
  },
];

/**
 * Todas las opciones declaradas, aplanadas.
 *
 * @returns {Array<MenuItem>}
 */
export function getMenuEntries() {
  return MENU_GROUPS.flatMap((group) => group.items);
}

/**
 * Opción declarada para una ruta, o null si la ruta no está en el catálogo.
 *
 * La comparación ignora la barra final: /dashboard y /dashboard/ son la misma
 * opción.
 *
 * @param {string} pathname
 * @returns {MenuItem|null}
 */
export function findEntryByPath(pathname) {
  const normalized = normalizePath(pathname);

  return getMenuEntries().find((entry) => normalizePath(entry.path) === normalized) ?? null;
}

/**
 * Roles autorizados para una ruta, tomados del catálogo declarativo.
 *
 * Una ruta sin opción declarada devuelve lista vacía: no exige rol concreto.
 * Autorización efectiva en el backend; aquí solo se refleja.
 *
 * @param {string} pathname
 * @returns {string[]}
 */
export function getRolesForPath(pathname) {
  return findEntryByPath(pathname)?.roles ?? [];
}

/**
 * Indica si una ruta corresponde a una opción operativa en esta fase.
 *
 * @param {string} pathname
 * @returns {boolean}
 */
export function isPathEnabled(pathname) {
  return findEntryByPath(pathname)?.enabled === true;
}

/**
 * Grupos visibles para un usuario: solo los grupos que tienen al menos una
 * opción autorizada, y solo con sus opciones autorizadas.
 *
 * Los roles se comparan contra `roles[].code` que devuelve GET /api/me.
 *
 * @param {{ roles?: Array<{ code: string }> } | null} user
 * @returns {Array<MenuGroup>}
 */
export function getMenuGroupsForUser(user) {
  const granted = getGrantedRoles(user);

  return MENU_GROUPS.map((group) => ({
    key: group.key,
    label: group.label,
    icon: group.icon,
    items: group.items.filter((item) => isAllowedFor(item, granted)),
  })).filter((group) => group.items.length > 0);
}

/**
 * Opciones declaradas visibles para un usuario, con su estado operativo.
 *
 * @param {{ roles?: Array<{ code: string }> } | null} user
 * @returns {Array<MenuItem>}
 */
export function getMenuEntriesForUser(user) {
  const granted = getGrantedRoles(user);

  return getMenuEntries().filter((entry) => isAllowedFor(entry, granted));
}

/**
 * @param {MenuItem} item
 * @param {string[]} granted
 * @returns {boolean}
 */
function isAllowedFor(item, granted) {
  return item.roles.some((role) => granted.includes(role));
}

/**
 * @param {string} pathname
 * @returns {string}
 */
function normalizePath(pathname) {
  if (pathname.length > 1 && pathname.endsWith("/")) {
    return pathname.slice(0, -1);
  }

  return pathname;
}

/**
 * @param {{ roles?: Array<{ code: string }> } | null} user
 * @returns {string[]}
 */
function getGrantedRoles(user) {
  if (!user || !Array.isArray(user.roles)) {
    return [];
  }

  return user.roles.map((role) => role?.code).filter(Boolean);
}

export default MENU_GROUPS;

/**
 * @typedef {object} MenuItem
 * @property {string} key
 * @property {string} label
 * @property {string} path
 * @property {string} icon
 * @property {string[]} roles
 * @property {boolean} enabled
 * @property {string|null} pendingReason
 *
 * @typedef {object} MenuGroup
 * @property {string} key
 * @property {string} label
 * @property {string} icon
 * @property {MenuItem[]} items
 */