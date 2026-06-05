-- Base local PostgreSQL para EkinDesk (Laragon). Ejecutar: psql -U postgres -h 127.0.0.1 -f scripts/local-pgsql-init.sql

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'ekindesk') THEN
        CREATE ROLE ekindesk WITH LOGIN PASSWORD 'secret' CREATEDB;
    END IF;
END
$$;

SELECT 'CREATE DATABASE ekindesk_local OWNER ekindesk'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ekindesk_local')\gexec

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'ekindesk_app') THEN
        CREATE ROLE ekindesk_app WITH LOGIN PASSWORD 'secret' NOSUPERUSER INHERIT;
    END IF;
END
$$;

GRANT ALL PRIVILEGES ON DATABASE ekindesk_local TO ekindesk_app;
