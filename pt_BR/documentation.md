---
title: Documentação
description: Event and booking management
---

Este plugin fornece:

* Gestão de eventos,
* Associar atividades a eventos,
* Gestão de reservas.

## Instalação

Primeiramente, baixe o plugin:

* [Get latest Events
  plugin!](https://github.com/galette-plugins/plugin-events/releases/latest)
* [Get Events plugin nightly
  build!](https://github.com/galette-plugins/plugin-events/releases/tag/nightly)

Extraia o arquivo baixado no diretório `plugins` do Galette. Por exemplo, no
Linux (substituindo `{url}` e `{version}` pelos valores corretos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-events-{version}.tar.bz2
```

## Inicialização do banco de dados

Para funcionar, este plugin requer várias tabelas no banco de dados. Consulte
[Interface de gerenciamento de plugins do
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

E está concluído; o plugin de eventos está instalado :)

## Plugin usage

Quando o plugin é instalado, um grupo `Eventos` é adicionado ao menu Galette
quando um usuário faz login. Existem várias possibilidades que mudam dependendo
do perfil do usuário (membro simples, gerente de grupo, administrador, etc.).

### Atividades

Você pode definir quantas atividades quiser e associá-las a um evento. Uma
atividade pode ser uma viagem organizada, uma refeição, uma hospedagem, etc.

![The list of activities](images/list_activities.png)

Uma atividade é composta por um nome, um status e um comentário opcional.

Para adicionar uma nova atividade, basta clicar no link "Nova atividade":

![The form of a new activity](images/new_activity.png)

### Eventos

O principal objetivo do plugin são os eventos. Você pode definir diversas
informações, como nome, datas de início e término, local, etc.

![The form of a new event](images/new_event.png)

Nome, data de início e cidade são obrigatórios. Todas as outras informações são
totalmente opcionais.

Eventos que não estejam vinculados a um grupo estarão disponíveis para todos os
membros. Se um grupo for criado, somente os membros e administradores desse
grupo terão acesso.

> **Note**
> 
> Quando um administrador de grupo cria um novo evento, ele deve escolher um dos
> grupos que ele administra!

Você pode associar uma ou várias atividades a cada evento e, para cada uma,
definir se ela está disponível, indisponível ou obrigatória. Escolha a atividade
que deseja adicionar e clique no botão.

![The activities attached to an event](images/event_activities.png)

> **Warning**
> 
> Adicionar ou remover uma atividade de um evento recarregará a página e
> solicitará que você preencha informações obrigatórias. No entanto (e isso é
> especificado em cada caso), o evento **não será armazenado** durante essa
> operação.
> 
> Não se esqueça de salvar o evento :)

Na lista de eventos, você pode editar ou remover entradas, acessar a lista de
reservas ou exportar reservas como CSV.

![The list of events](images/events_list.png)

### Reservas

É possível fazer reservas para cada evento. Como já mencionamos, membros comuns
e administradores de grupos terão acesso limitado aos eventos de seus
respectivos grupos, ou seja, aos eventos que não são restritos a um grupo.

É possível adicionar uma nova reserva através do menu "Nova reserva" ou na lista
de reservas de eventos.

![The form of a new booking](images/new_booking.png)

As reservas são encerradas assim que o evento é marcado como encerrado ou quando
a data de início termina. Administradores e funcionários podem sempre adicionar
novas reservas.

A lista de atividades é obtida a partir do evento; as atividades obrigatórias
devem ser selecionadas no momento da reserva.

![The list of bookings](images/bookings_list.png)

Você pode filtrar a lista de reservas por evento, tipo de pagamento ou status do
pagamento. Em seguida, você pode enviar um e-mail aos membros que fizeram
reservas, usando o mecanismo de e-mail padrão do Galette.
