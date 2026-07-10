-- Base local PostgreSQL para Tikara (Laragon). Ejecutar: psql -U postgres -h 127.0.0.1 -f scripts/local-pgsql-init.sql

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'tikara') THEN
        CREATE ROLE tikara WITH LOGIN PASSWORD 'secret' CREATEDB;
    END IF;
END
$$;

SELECT 'CREATE DATABASE tikara_local OWNER tikara'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'tikara_local')\gexec

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'tikara_app') THEN
        CREATE ROLE tikara_app WITH LOGIN PASSWORD 'secret' NOSUPERUSER INHERIT;
    END IF;
END
$$;

GRANT ALL PRIVILEGES ON DATABASE tikara_local TO tikara_app;
