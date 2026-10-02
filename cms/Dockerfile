FROM node:22-alpine

WORKDIR /app

# Instalar herramientas necesarias para sharp y compilación nativa si hiciera falta
RUN apk add --no-cache libc6-compat

# Instalar dependencias
COPY package.json package-lock.json* ./
RUN npm install

# Copiar el código fuente
COPY . .

ENV PORT=3000
ENV NODE_ENV=development
ENV DATABASE_URL=file:/app/data/cms.db
ENV PAYLOAD_SECRET=development-secret-key-change-in-production

# Directorio de datos para persistencia SQLite y Media
RUN mkdir -p /app/data /app/media

EXPOSE 3000

CMD ["npm", "run", "dev"]
