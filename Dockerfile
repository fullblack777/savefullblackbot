# Dockerfile
FROM php:8.2-cli

# Copiar arquivos
COPY . /app/
WORKDIR /app/

# Criar pastas
RUN mkdir -p data api && \
    chmod -R 755 data api

EXPOSE ${PORT}

CMD php -S 0.0.0.0:${PORT} -t ./
