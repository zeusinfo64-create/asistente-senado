# Dockerfile - Frontend Mesa de Ayuda TI (Fase 7.1.1)
# Imagen base ligera para Node 18 (compatible con Vite 5)
FROM node:18-alpine

# Establecer directorio de trabajo
WORKDIR /app

# Copiar package.json y package-lock.json para instalación reproducible
COPY package.json package-lock.json ./

# Instalar dependencias (sin node_modules del host)
RUN npm ci --silent

# Copiar código fuente
COPY . .

# Exponer puerto Vite
EXPOSE 5173

# Comando por defecto para desarrollo
CMD ["npm", "run", "dev"]