FROM php:8.3-cli

# System certs + curl support for outgoing HTTPS API calls
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy project
COPY . /app

# Render provides PORT at runtime
CMD sh -c 'php -S 0.0.0.0:${PORT:-10000} -t /app'

