FROM node:18.20-alpine3.21 AS builder
ARG api_url
ENV API_URL=${api_url}
ENV NODE_ENV=development
ARG port
ENV PORT=${port}
WORKDIR /app
RUN echo -e "PORT=${PORT}" >> /app/.env
RUN echo -e "HOST=0.0.0.0" >> /app/.env
RUN echo -e "API_URL=${API_URL}" >> /app/.env
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# production stage
FROM nginx:stable-alpine AS production-stage
COPY --from=builder /app/dist /usr/share/nginx/html
CMD ["nginx", "-g", "daemon off;"]
