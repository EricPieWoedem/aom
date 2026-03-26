FROM php:8.3-cli

# System certs + cURL extension dependencies for outgoing HTTPS API calls
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        pkg-config \
        libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy project
COPY . /app

# Render provides PORT at runtime
CMD sh -c 'php -S 0.0.0.0:${PORT:-10000} -t /app'

