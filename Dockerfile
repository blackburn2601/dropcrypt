# Basis-Image mit Python 3.11
FROM python:3.11-slim

# Set Arbeitsverzeichnis im Container
WORKDIR /app

# Install build dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    python3-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for better caching
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Projektdateien kopieren
COPY . .

# Übersetzungen kompilieren
RUN pybabel compile -d translations

# Port definieren
EXPOSE 5000

# Startbefehl
CMD ["python", "-u", "app.py"]
