# Mesa de Ayuda TI

Base visual del sistema de Mesa de Ayuda de la Unidad de Tecnologías de
Información.

## Requisitos

- Node.js 18 o superior
- npm

## Instalación

```bash
npm install
```

## Comandos disponibles

```bash
npm run dev      # servidor de desarrollo
npm run build    # build de producción en dist/
npm run preview  # previsualiza el build de producción
npm run lint     # ESLint
```

## Estructura

```
src/
├── assets/
│   ├── icons/    # flaticon (única familia de iconos en uso)
│   ├── images/   # avatar provisional
│   ├── scss/     # estilos del template
│   └── vendor/   # bootstrap 4 y librerias de soporte
├── context/
│   └── ThemeContext.jsx
├── layouts/
│   ├── nav/
│   │   ├── Header.jsx
│   │   ├── Menu.jsx
│   │   ├── Nav.jsx
│   │   ├── NavHeader.jsx
│   │   └── Sidebar.jsx
│   ├── Footer.jsx
│   ├── PageTitle.jsx
│   └── ScrollToTop.jsx
├── pages/
│   └── Dashboard.jsx
├── routes/
│   └── AppRoutes.jsx
├── App.jsx
└── main.jsx
```

## Rutas

- `/` y `/dashboard`dirigen al Dashboard provisional.
- El menú lateral ya incluye la estructura conceptual de los roles ADMIN, TECH y
  REQUESTER. Los destinos no implementados se muestran deshabilitados hasta que
  existan sus páginas.

## Alcance actual

Fase de limpieza visual. No incluye autenticación, autorización por roles,
llamadas a la API, ni gestión de tickets, usuarios, activos o reportes.