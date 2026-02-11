# Cómo redirigir correos a la cuenta matriz (Gmail)

La aplicación GAC **solo lee** la cuenta matriz; no configura el reenvío. Tú (o cada usuario) debes configurar en **Gmail** que los correos se reenvíen a la cuenta matriz, por ejemplo **primo.021618@gmail.com**.

---

## Opción 1: Reenvío automático (Forwarding) en Gmail

Así, todo lo que llegue a **una cuenta Gmail** se reenvía a **primo.021618@gmail.com**.

### En la cuenta que QUIERES que reenvíe (la “origen”, ej. usuario@gmail.com)

1. Abre **Gmail** con esa cuenta.
2. Arriba a la derecha: **engranaje** → **Ver toda la configuración**.
3. Pestaña **Reenvío y correo POP/IMAP**.
4. En **Reenvío**, clic en **Añadir una dirección de reenvío**.
5. Escribe **primo.021618@gmail.com** y **Siguiente** / **Continuar**.
6. Gmail enviará un **código de verificación** al correo **primo.021618@gmail.com**.
7. Entra en **primo.021618@gmail.com**, abre ese correo y haz clic en el enlace o copia el código.
8. Vuelve a la cuenta origen, pega el código y confirma.
9. Elige:
   - **Reenviar una copia** → si quieres que en la cuenta origen también se guarde una copia.
   - O **Archivar** / **Eliminar** el original si solo quieres que llegue a la matriz.
10. **Guardar cambios**.

Desde ese momento, todo lo que llegue a la cuenta origen se reenvía a **primo.021618@gmail.com**. GAC leerá la matriz y tomará el destinatario real de los headers (To / X-Original-To).

---

## Opción 2: Varias cuentas → una sola matriz

Si tienes varias cuentas (usuario1@gmail.com, usuario2@gmail.com, …) y quieres que todo llegue a **primo.021618@gmail.com**:

- Repite la **Opción 1** en **cada** cuenta origen, poniendo en todos los casos la dirección de reenvío **primo.021618@gmail.com**.
- No hace falta tocar nada en la cuenta **primo.021618@gmail.com** para “recibir” los reenvíos; Gmail lo hace solo.

---

## Opción 3: Solo reenviar ciertos correos (filtros)

Si quieres reenviar **solo** los correos que tengan cierto asunto o remitente:

1. En la cuenta origen: **Configuración** (engranaje) → **Ver toda la configuración**.
2. Pestaña **Filtros y direcciones bloqueadas** → **Crear un nuevo filtro**.
3. Rellena, por ejemplo:
   - **De:** remitente concreto (ej. no-reply@disneyplus.com).
   - **Asunto:** palabras que suelen llevar los códigos (ej. “código”, “verificación”).
4. **Crear filtro**.
5. Marca **Reenviarlo a: primo.021618@gmail.com**.
6. **Crear filtro** otra vez.

Solo los correos que cumplan el filtro se reenviarán a la matriz.

---

## Resumen

| Objetivo | Dónde se hace |
|----------|----------------|
| Que todo lo de una cuenta vaya a la matriz | En esa cuenta Gmail: Configuración → Reenvío → Añadir **primo.021618@gmail.com** y verificar. |
| Que varias cuentas reenvíen a la matriz | Mismo reenvío en cada cuenta, todas apuntando a **primo.021618@gmail.com**. |
| Que solo algunos correos se reenvíen | En la cuenta origen: Filtros → crear filtro (asunto/remitente) → acción “Reenviar a primo.021618@gmail.com”. |

La cuenta **primo.021618@gmail.com** debe estar configurada en GAC como **cuenta Gmail matriz** (Configuración del sistema → Configurar cuenta Google). GAC no redirige correos; solo lee lo que ya llega a esa cuenta.
