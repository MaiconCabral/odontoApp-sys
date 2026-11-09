# Nuxt frontend as a Node server (.output). Build from the repo root:
#   docker build -f deploy/web.Dockerfile -t odonto-web .
FROM node:20.19-alpine AS build

WORKDIR /app

# postinstall runs nuxt prepare, which needs the sources; it runs as part of the build below.
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund

COPY frontend/ ./
RUN npm run build

FROM node:20.19-alpine

WORKDIR /app
ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3000

COPY --from=build --chown=node:node /app/.output ./.output

USER node
EXPOSE 3000

# NUXT_PUBLIC_API_BASE, NUXT_PUBLIC_CENTRAL_DOMAIN and NUXT_PUBLIC_SALES_CONTACT_URL are read at runtime.
CMD ["node", ".output/server/index.mjs"]
