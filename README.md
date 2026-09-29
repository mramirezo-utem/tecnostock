# TecnoStock – Sistema de inventario

Proyecto del Taller de Sistemas de Información (Caso 1). Aplicación web para gestionar el catálogo de productos y los movimientos de inventario (entradas y salidas) de TecnoStock, un emprendimiento que vende accesorios tecnológicos.

**Estudiante:** Matías Javier Ramírez Osorio · **Sección:** 413 · **Docente:** VICTOR HEUGHES ESCOBAR JERIA

**Tecnologías:** HTML5, Bootstrap 5, JavaScript, PHP 8 con PDO, MySQL (XAMPP + phpMyAdmin).

---

## 1. Análisis

### 1.1 Problema

TecnoStock registra sus productos en una planilla. Esto ha provocado códigos duplicados, errores en las cantidades y poca claridad sobre el stock real.

### 1.2 Solución propuesta

Una aplicación web sencilla, que funciona en un servidor local, que centraliza el catálogo y los movimientos de existencias y mantiene la integridad de los datos.

### 1.3 Actor

| Actor | Responsabilidad |
| --- | --- |
| Encargado de inventario | Inicia sesión, administra productos y registra entradas y salidas de existencias. |

### 1.4 Alcance

**Incluido:** inicio de sesión, gestión de productos (crear, listar, buscar, editar, desactivar), registro de movimientos, actualización automática del stock y alerta de stock bajo.

**No incluido:** administración de usuarios y de categorías desde la interfaz (se cargan directamente en la base de datos), reportes, ventas, proveedores.

**Supuestos:**
- El único usuario del sistema es el encargado de inventario, previamente registrado en la base de datos.
- Las categorías se cargan previamente en la base de datos.
- Al editar un producto se pueden modificar precio, descripción, categoría y cantidades, tal como pide el caso. El cambio de stock hecho desde la edición no genera un movimiento; para entradas y salidas normales se usa la pantalla de movimientos.

### 1.5 Requerimientos funcionales

| ID | Requerimiento |
| --- | --- |
| RF01 | Iniciar sesión con un usuario previamente registrado. |
| RF02 | Registrar productos con código, nombre, categoría, descripción, precio, stock actual y stock mínimo. |
| RF03 | Consultar el listado de productos. |
| RF04 | Buscar productos por nombre, código o categoría. |
| RF05 | Modificar precio, descripción, categoría y cantidades de un producto. |
| RF06 | Desactivar productos que ya no se comercializan. |
| RF07 | Registrar entradas y salidas de existencias. |
| RF08 | Mostrar una alerta cuando el stock actual sea menor que el stock mínimo. |

### 1.6 Requerimientos no funcionales

| ID | Requerimiento |
| --- | --- |
| RNF01 | Funcionar en un servidor local (XAMPP), sin frameworks complejos. |
| RNF02 | Acceso a datos con PDO y consultas preparadas. |
| RNF03 | Validaciones en JavaScript, repetidas en PHP. |
| RNF04 | Interfaz clara y responsiva con Bootstrap. |
| RNF05 | Claves de usuario almacenadas encriptadas (`password_hash` / `password_verify`). |

### 1.7 Reglas de negocio

| ID | Regla |
| --- | --- |
| RN01 | El código de producto no puede repetirse. |
| RN02 | Precio, stock actual, stock mínimo y cantidad de un movimiento no pueden ser negativos. |
| RN03 | Todo movimiento registra fecha, tipo, cantidad, producto y usuario responsable. |
| RN04 | Una salida no puede dejar el stock bajo cero. |
| RN05 | Los productos no se eliminan: se desactivan (`estado = 0`). |
| RN06 | El stock se actualiza automáticamente después de cada entrada o salida válida. |

---

## 2. Modelo de datos

Base de datos `tecnostock` con cuatro tablas relacionadas:

| Tabla | Campos principales | Relaciones |
| --- | --- | --- |
| `usuario` | idUsuario (PK), nombre, correo (único), clave, estado | – |
| `categoria` | idCategoria (PK), nombre (único), descripcion, estado | – |
| `producto` | idProducto (PK), codigo (único), nombre, descripcion, precio, stockActual, stockMinimo, estado, idCategoria (FK) | `idCategoria` → `categoria` |
| `movimiento` | idMovimiento (PK), fechaHora, tipo (entrada/salida), cantidad, observacion, idProducto (FK), idUsuario (FK) | `idProducto` → `producto`, `idUsuario` → `usuario` |

Restricciones en la base: `UNIQUE` en el código de producto, `CHECK` para precio y stocks no negativos y cantidad mayor que 0, y claves foráneas entre las tablas.

---

## 3. Trazabilidad: requerimiento → implementación

| Requerimiento | Archivo(s) | Pruebas |
| --- | --- | --- |
| RF01 Inicio de sesión | `login.php`, `verificarSesion.php`, `cerrarSesion.php` | CP01–CP06 |
| RF02 Registrar productos | `productoForm.php` | CP12–CP16 |
| RF03 Listado | `productos.php` | CP07 |
| RF04 Búsqueda | `productos.php` | CP08–CP11 |
| RF05 Modificar producto | `productoForm.php` | CP17–CP18 |
| RF06 Desactivar producto | `desactivarProducto.php` | CP19–CP20 |
| RF07 Entradas y salidas | `movimientos.php` | CP21–CP25, CP29 |
| RF08 Alerta de stock bajo | `productos.php`, `inicio.php` | CP26–CP28 |
| RN01 Código único | `productoForm.php` + `UNIQUE` en la base | CP13 |
| RN02 Sin valores negativos | validaciones JS/PHP + `CHECK` en la base | CP14, CP16, CP24, CP25 |
| RN03 Datos del movimiento | tabla `movimiento`, `movimientos.php` | CP29 |
| RN04 Salida sin stock negativo | `movimientos.php` (validación + `UPDATE` condicionado) | CP23 |
| RN05 Desactivar, no eliminar | `desactivarProducto.php` | CP19 |
| RN06 Stock automático | `movimientos.php` (transacción) | CP21, CP22 |

---

## 4. Estructura del proyecto

```
tecnostock/
├── tecnostock.sql          Script de la base de datos (estructura + datos de prueba)
├── conexion.php            Conexión PDO a MySQL
├── login.php               Inicio de sesión
├── verificarSesion.php     Protege las páginas que requieren sesión
├── cerrarSesion.php        Cierre de sesión
├── menu.php                Barra de navegación compartida
├── inicio.php              Página de bienvenida y aviso de stock bajo
├── productos.php           Listado, búsqueda y alerta de stock bajo
├── productoForm.php        Crear y editar productos
├── desactivarProducto.php  Desactivación de productos
└── movimientos.php         Entradas, salidas e historial
```

---

## 5. Cómo ejecutar el proyecto

1. Instalar **XAMPP** e iniciar **Apache** y **MySQL** desde su panel de control.
2. Copiar la carpeta `tecnostock` dentro de `C:\xampp\htdocs\`.
3. Abrir `http://localhost/phpmyadmin`, ir a la pestaña **Importar** y cargar el archivo `tecnostock.sql`.
4. Abrir `http://localhost/tecnostock/login.php`.
5. Iniciar sesión con el usuario de prueba:
   - Correo: `admin@tecnostock.cl`
   - Clave: `admin123`

---

## 6. Pruebas

**Datos de prueba iniciales (incluidos en el script SQL):**

| Código | Producto | Stock | Mínimo |
| --- | --- | --- | --- |
| AUD-001 | Audífonos Bluetooth | 25 | 5 |
| CAB-001 | Cable USB-C 1m | 3 | 10 (stock bajo a propósito) |
| PER-001 | Mouse inalámbrico | 12 | 4 |

**Casos de prueba**:

| ID | Req. | Caso | Pasos / datos | Resultado esperado | Resultado obtenido | Evidencia |
| --- | --- | --- | --- | --- | --- | --- |
| CP01 | RF01 | Login correcto | admin@tecnostock.cl / admin123 | Entra a la página de inicio | ☐ Aprobó ☐ Falló | captura |
| CP02 | RF01 | Clave incorrecta | Correo correcto, clave errónea | Mensaje "Correo o clave incorrectos" | ☐ Aprobó ☐ Falló | captura |
| CP03 | RF01 | Campos vacíos | Enviar el formulario vacío | Aviso de que faltan datos, sin recargar | ☐ Aprobó ☐ Falló | captura |
| CP04 | RF01 | Acceso sin sesión | Abrir `inicio.php` sin iniciar sesión | Redirige al login | ☐ Aprobó ☐ Falló | captura |
| CP05 | RF01 | Cerrar sesión | Botón "Cerrar sesión" y luego abrir `productos.php` | Vuelve al login | ☐ Aprobó ☐ Falló | captura |
| CP06 | RF01 | Ver/ocultar clave | Presionar el ojo en el login | La clave se muestra y se oculta | ☐ Aprobó ☐ Falló | captura |
| CP07 | RF03 | Listado | Entrar a Productos | Se ven los productos con categoría, precio y stock | ☐ Aprobó ☐ Falló | captura |
| CP08 | RF04 | Buscar por nombre | Buscar `mouse` | Solo aparece el mouse | ☐ Aprobó ☐ Falló | captura |
| CP09 | RF04 | Buscar por código | Buscar `AUD-001` | Solo aparecen los audífonos | ☐ Aprobó ☐ Falló | captura |
| CP10 | RF04 | Buscar por categoría | Buscar `cables` | Solo aparece el cable USB-C | ☐ Aprobó ☐ Falló | captura |
| CP11 | RF04 | Búsqueda sin resultados | Buscar `zzz` | Mensaje "No se encontraron productos" | ☐ Aprobó ☐ Falló | captura |
| CP12 | RF02 | Crear producto válido | Código TEC-001, nombre, categoría, precio 5000, stock 10, mínimo 2 | Aparece en el listado con mensaje de éxito | ☐ Aprobó ☐ Falló | captura |
| CP13 | RN01 | Código duplicado | Crear otro producto con código AUD-001 | Rechaza: "Ya existe un producto con ese código" | ☐ Aprobó ☐ Falló | captura |
| CP14 | RN02 | Precio negativo | Precio -100 | Rechaza con mensaje, no guarda | ☐ Aprobó ☐ Falló | captura |
| CP15 | RF02 | Campos obligatorios vacíos | Dejar nombre vacío | Rechaza con mensaje, no guarda | ☐ Aprobó ☐ Falló | captura |
| CP16 | RN02 | Stock negativo | Stock actual -5 | Rechaza con mensaje, no guarda | ☐ Aprobó ☐ Falló | captura |
| CP17 | RF05 | Editar precio | Cambiar el precio del mouse de 8990 a 9990 | El listado muestra el nuevo precio | ☐ Aprobó ☐ Falló | captura |
| CP18 | RF05 | Código no editable | Abrir la edición de un producto | El campo código está bloqueado | ☐ Aprobó ☐ Falló | captura |
| CP19 | RF06 | Desactivar | Desactivar el producto TEC-001 | Fila gris "Desactivado"; en la base `estado = 0` y el registro sigue existiendo | ☐ Aprobó ☐ Falló | captura |
| CP20 | RF06 | Producto desactivado en movimientos | Abrir la lista de productos en Movimientos | El producto desactivado no aparece | ☐ Aprobó ☐ Falló | captura |
| CP21 | RF07 | Entrada válida | Entrada de 5 unidades del mouse | Stock sube de 12 a 17; queda en el historial | ☐ Aprobó ☐ Falló | captura |
| CP22 | RF07 | Salida válida | Salida de 3 unidades del mouse | Stock baja de 17 a 14; queda en el historial | ☐ Aprobó ☐ Falló | captura |
| CP23 | RN04 | Salida mayor al stock | Salida de 500 unidades | Rechaza con "Stock insuficiente"; el stock no cambia | ☐ Aprobó ☐ Falló | captura |
| CP24 | RN02 | Cantidad cero | Cantidad 0 | Rechaza con mensaje, no guarda | ☐ Aprobó ☐ Falló | captura |
| CP25 | RN02 | Cantidad negativa | Cantidad -4 | Rechaza con mensaje, no guarda | ☐ Aprobó ☐ Falló | captura |
| CP26 | RF08 | Alerta de stock bajo | Entrar a Inicio y a Productos con el cable USB-C en 3 (mínimo 10) | Aviso amarillo, fila amarilla y etiqueta "Stock bajo" | ☐ Aprobó ☐ Falló | captura |
| CP27 | RF08 | Alerta desaparece | Entrada de 20 unidades del cable | Sin etiqueta ni aviso para ese producto | ☐ Aprobó ☐ Falló | captura |
| CP28 | RF08 | Borde: stock igual al mínimo | Dejar un producto con stock igual a su mínimo | No muestra alerta (la regla es "menor que") | ☐ Aprobó ☐ Falló | captura |
| CP29 | RN03 | Datos del movimiento | Revisar la tabla `movimiento` en phpMyAdmin | Cada fila tiene fecha, tipo, cantidad, producto y usuario | ☐ Aprobó ☐ Falló | captura |

---

## 7. Seguridad y calidad

- Todas las consultas usan **PDO con consultas preparadas**.
- Las claves se guardan encriptadas con `password_hash` y se comprueban con `password_verify`.
- Las páginas protegidas incluyen `verificarSesion.php`.
- Los textos se muestran con `htmlspecialchars` para evitar inyección de código HTML.
- El registro de movimientos usa una **transacción**: el stock y el historial se actualizan juntos o no se actualiza ninguno.
