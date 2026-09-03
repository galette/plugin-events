---
title: Documentazione
description: Event and booking management
---

Questo componente aggiuntivo fornisce:

* gestione eventi,
* attività associate agli eventi,
* gestione prenotazioni.

## Installazione

Prima di tutto, scaricare il plugin:

* [Get latest Events
  plugin!](https://github.com/galette-plugins/plugin-events/releases/latest)
* [Get Events plugin nightly
  build!](https://github.com/galette-plugins/plugin-events/releases/tag/nightly)

Estrai l'archivio scaricato nella cartella `plugins` di Galette. Per esempio, su
Linux (sostituendo `{url}` e `{version}` con i rispettivi valori):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-events-{version}.tar.bz2
```

## Inizializzazione del database

Per poter funzionare, questo componente aggiuntivo richiede diverse nuove
tabelle nel database. Vedi [Interfaccia di gestione dei componenti aggiuntivi di
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

E questo è finito; il plugin Eventi è stato installato :)

## Plugin usage

Quando il plugin è installato, un gruppo `Eventi` viene aggiunto al menu Galette
quando un utente è connesso. Ci sono varie possibilità che cambiano a seconda
del profilo utente (socio semplice, gestore del gruppo, amministratore, ...).

### Attività

Si può definire tutte le attività che si vuole e associarle a un evento.
Un'attività può essere un viaggio organizzato, un pasto, un alloggio, ...

![The list of activities](images/list_activities.png)

Un'attività è composta da un nome, uno stato e un commento facoltativo.

Per aggiungere una nuova attività, basta fare clic sul collegamento "Nuova
attività":

![The form of a new activity](images/new_activity.png)

### Eventi

Gli eventi sono l'obiettivo principale del plugin. È possibile definire diverse
informazioni, come un nome, le date di inizio e di fine, la posizione, ...

![The form of a new event](images/new_event.png)

Nome, data di inizio e città sono obbligatori. Tutte le altre informazioni sono
completamente facoltative.

Gli eventi che non sono collegati a un gruppo saranno disponibili per tutti i
membri. Se un gruppo è impostato, solo i membri e i gestori di questo gruppo
avranno accesso.

> **Note**
> 
> Quando un gestore di gruppo crea un nuovo evento, deve scegliere uno dei
> gruppi che possiede!

È possibile allegare una o più attività a ogni evento, e per ognuna impostare se
è disponibile, non disponibile o anche obbligatoria. Scegliere l'attività da
aggiungere e fare clic sul pulsante.

![The activities attached to an event](images/event_activities.png)

> **Warning**
> 
> L'aggiunta o la rimozione di attività da un evento ricaricherà la pagina e
> provocherà la richiesta di inserire le necessarie informazioni. Tuttavia (e
> questo viene di volta in volta specificato), l'evento **non verrà
> memorizzato** durante questa operazione.
> 
> Assicurarsi di salvare l'evento :)

Dall'elenco Eventi si può modificare o rimuovere voci, accedere all'elenco delle
prenotazioni o esportare le prenotazioni come CSV.

![The list of events](images/events_list.png)

### Prenotazioni

Per ogni evento è possibile registrare le prenotazioni. Come detto prima, i
membri semplici e i gestori dei gruppi saranno limitati agli eventi dei loro
gruppi, oppure agli eventi che non sono limitati a un gruppo.

L'aggiunta di una nuova prenotazione può essere effettuata dal menu "Nuova
prenotazione" o dall'elenco delle prenotazioni degli eventi.

![The form of a new booking](images/new_booking.png)

Le prenotazioni vengono chiuse una volta che l'evento viene contrassegnato come
chiuso o al termine della data di inizio. Gli amministratori e i membri del
personale possono sempre aggiungere nuove prenotazioni.

L'elenco attività viene recuperato dall'evento; quelle obbligatorie vanno
ovviamente verificate in fase di prenotazione.

![The list of bookings](images/bookings_list.png)

Si può filtrare l'elenco delle prenotazioni per evento, tipo di pagamento o
stato del pagamento. Poi si potrà inviare una mail ai membri prenotati,
utilizzando il meccanismo dell'invio postale standard di Galette.
