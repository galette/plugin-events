---
title: Documentación
description: Event and booking management
---

Este complemento proporciona:

* gestión de eventos,
* asocie actividades con eventos,
* gestión de reservas.

## Instalación

Lo primero de todo, descarga el complemento:

* [Get latest Events
  plugin!](https://github.com/galette-plugins/plugin-events/releases/latest)
* [Get Events plugin nightly
  build!](https://github.com/galette-plugins/plugin-events/releases/tag/nightly)

Extrae el archivo descargado en la carpeta `plugin` de Galette . Por ejemplo, en
linux (sustituyendo `{url}` y `{version}` con los valores correctos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-events-{version}.tar.bz2
```

## Inicialización de base de datos

Para que funcione, este complemento necesita varias tablas en la base de datos.
Consulta [la interfaz de gestión de complementos de
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Y esto está finalizado; el complemento de Eventos está instalado :)

## Plugin usage

Cuando es complemento se instala, se añade un grupo `Events` al menú Galette
cuando un usuario entre en el equipo. Hay varias posibilidades que cambiar
dependiendo del perfil del usuario (miembro simple, gestor de grupo,
administrador, ...).

### Actividades

Puede definir tantas actividades como desee, y asociarlos a un evento. Una
actividad puede ser un viaje organizado, una comida, una casa, ...

![The list of activities](images/list_activities.png)

Una actividad está compuesta con un nombre, un estado y comentario opcional.

Para añadir una activad nueva, tan solo pulse en el enlace «Crear actividad»:

![The form of a new activity](images/new_activity.png)

### Eventos

Los eventos son el objetivo principal del complemento. Puede definir varias
informaciones, como un nombre, fechas que comienzan y finalizan, lugares, ...

![The form of a new event](images/new_event.png)

Nombre, datos de comienzo y ciudad son obligatorios. Toda las demás
informaciones son completamente opcionales.

Los eventos que no están enlazados a un grupo estarán disponibles para todos los
miembros. Si un grupo está fijado, solamente los miembros y gestores de este
grupo tendrán acceso.

> **Note**
> 
> ¡ Cuando un gestor de grupo crea un evento nuevo, debe elegir uno de los
> grupos que pertenece !

Puede adjuntar una o varias actividades por cada evento, y por cada uno fija si
está disponible, no disponible o incluso obligatorio. Elija la actividad para
añadir, y pulse el botón.

![The activities attached to an event](images/event_activities.png)

> **Warning**
> 
> Añadiendo o quitando actividad desde un evento recargará la página y le
> preguntará rellenar información obligatoria. Sin embargo (y esto está
> especificado cada vez), el evento **no será almacenado** durante esta
> operación.
> 
> Asegure que guarde el evento :)

Desde el índice de Eventos, puede editar o quitar apuntes, acceder al listado de
reservas de exportar reservas como CSV.

![The list of events](images/events_list.png)

### Reservas

Las reservas pueden ser registradas por cada evento. Tal como dijimos antes, los
miembros únicos y los gestores de grupos estarán limitados a sus eventos de
grupos, o para los eventos que no están restringidos a un grupo.

Añadir una reserva nueva puede ser logrado desde el menú «Reserva nueva» o desde
el índice de evento de reservas.

![The form of a new booking](images/new_booking.png)

Las reservas están cerradas una vez que el evento está mercado como cerrado, o
cuando la fecha de inicio está superada. Los administradores y los miembros del
personal siempre pueden agregar reservas nuevas.

El índice de actividades está revertido desde el evento; obligatoriamente unos
deben por supuesto ser revisado durante la reserva.

![The list of bookings](images/bookings_list.png)

Puede filtrar el índice de reservas por evento, tipo de remuneración o estado de
remuneración. Entonces puede enviar un correo a los miembros de la reserva,
utilizando el mecanismo de correo estándar de Galette.
