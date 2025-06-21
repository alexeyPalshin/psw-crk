FROM node:18.20-alpine3.21 as builder
ARG api_url
ENV API_URL=${api_url}
ENV NODE_ENV=development
ARG port
ENV PORT=${port}
WORKDIR /usr/src/app
RUN echo -e "PORT=${PORT}" >> /usr/src/app/.env
RUN echo -e "HOST=0.0.0.0" >> /usr/src/app/.env
RUN echo -e "API_URL=${API_URL}" >> /usr/src/app/.env
COPY package*.json ./
RUN npm ci
COPY . .
EXPOSE 3001