# Basis-Image mit Python 3.11
FROM python:3.11-slim

# Set Arbeitsverzeichnis im Container
WORKDIR /app

# Abhängigkeiten
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Projektdateien kopieren
COPY . .

# Übersetzungen kompilieren
RUN pybabel compile -d translations

# Port definieren (optional)
EXPOSE 5000

# Startbefehl
CMD ["python", "app.py"]
