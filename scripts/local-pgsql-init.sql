-- Base local PostgreSQL para Tikara. Ejecutar como superuser:
--   psql -U postgres -h 127.0.0.1 -f scripts/local-pgsql-init.sql
--
-- Dos bases con propósitos distintos, no intercambiables:
--
--   tikara_local (rol `tikara`, CREATEDB)  -- desarrollo normal (composer dev,
--     migrate:fresh --seed a mano). Sin relación con RLS/tests.
--
--   tikara_test (rol `tikara_app`, NOSUPERUSER)  -- la que exige
--     phpunit.pgsql.xml (`composer test:pgsql`, TENANCY_PGSQL_RLS=true).
--     Reproduce exactamente lo que hace el workflow de CI
--     (.github/workflows/tests.yml) para que "pasa en mi PG local" y "pasa
--     en CI" signifiquen lo mismo: tikara_app debe ser NOSUPERUSER y dueño
--     de la base y del schema `public` -- con FORCE ROW LEVEL SECURITY
--     (2026_07_09_000026 y siguientes), un superuser o un rol con BYPASSRLS
--     ignora las políticas, lo que dejaría pasar tests de RLS que en
--     producción sí deberían fallar.
--
-- Corrección 2026-07-14: esta segunda mitad del script antes creaba
-- `tikara_app` pero solo le daba GRANT ALL sobre `tikara_local` -- nunca
-- creaba `tikara_test`, que es la base que de verdad exige
-- phpunit.pgsql.xml. El entorno que sí pasa la suite ahora mismo fue armado
-- a mano por fuera de este script en algún momento sin documentar. Este
-- script ya refleja ese entorno real (verificado por lectura contra \du,
-- \l y \dp, sin recrear nada).

-- ── tikara_local: desarrollo normal ─────────────────────────────────────

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'tikara') THEN
        CREATE ROLE tikara WITH LOGIN PASSWORD 'secret' CREATEDB;
    END IF;
END
$$;

SELECT 'CREATE DATABASE tikara_local OWNER tikara'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'tikara_local')\gexec

-- ── tikara_test: la que corre phpunit.pgsql.xml / CI ────────────────────
-- Mismo procedimiento que .github/workflows/tests.yml: crear el rol
-- NOSUPERUSER primero, la base después, y transferirle la propiedad de la
-- base y del schema public (no basta con GRANT: con FORCE ROW LEVEL
-- SECURITY el dueño de la tabla también queda sujeto a RLS salvo que sea
-- superuser o BYPASSRLS, que es justo lo que se quiere probar).

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'tikara_app') THEN
        CREATE ROLE tikara_app WITH LOGIN PASSWORD 'secret' NOSUPERUSER INHERIT;
    END IF;
END
$$;

SELECT 'CREATE DATABASE tikara_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'tikara_test')\gexec

ALTER DATABASE tikara_test OWNER TO tikara_app;

\c tikara_test
ALTER SCHEMA public OWNER TO tikara_app;

-- ── Residuo ekindesk_db / ekindesk (rename de proyecto a Tikara) ────────
-- Sigue existiendo en este entorno (confirmado por \l: base ekindesk_db,
-- dueño ekindesk, con Bypass RLS) y es lo que sigue usando el .env
-- principal (DB_CONNECTION/DB_DATABASE/DB_USERNAME de desarrollo), no
-- tikara_local. Es independiente de tikara_test -- no lo toca este script
-- ni ninguno de los comandos corridos en esta sesión.
--
-- Antes de eliminarlo (acción pendiente del operador, NO ejecutada aquí):
--   1. Confirmar que ningún .env activo (local o de otro colaborador)
--      sigue apuntando a ekindesk_db/ekindesk.
--   2. `pg_dump` de respaldo si tiene datos que no estén también en
--      tikara_local/tikara_test.
--   3. Solo entonces: DROP DATABASE ekindesk_db; DROP ROLE ekindesk;
