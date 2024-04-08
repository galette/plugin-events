--
-- Table structure for table `galette_events_events`
--

DROP SEQUENCE IF EXISTS galette_events_events_id_seq;
CREATE SEQUENCE galette_events_events_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_events_events CASCADE;
CREATE TABLE galette_events_events (
  id_event integer DEFAULT nextval('galette_events_events_id_seq'::text) NOT NULL,
  name character varying(150) NOT NULL,
  address character varying(150) NOT NULL default '',
  zip character varying(10) NOT NULL default '',
  town character varying(50) NOT NULL default '',
  country character varying(50) default NULL,
  begin_date date default '19010101' NOT NULL,
  end_date date default '19010101' NOT NULL,
  creation_date date default '19010101' NOT NULL,
  is_open boolean default TRUE,
  id_group integer REFERENCES galette_groups(id_group) ON DELETE RESTRICT ON UPDATE CASCADE default NULL,
  comment text,
  color character vaying(7),
  PRIMARY KEY (id_event)
);

--
-- Table structure for table `galette_events_bookings`
--

DROP SEQUENCE IF EXISTS galette_events_bookings_id_seq;
CREATE SEQUENCE galette_events_bookings_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_events_bookings CASCADE;
CREATE TABLE galette_events_bookings (
  id_booking integer DEFAULT nextval('galette_events_bookings_id_seq'::text) NOT NULL,
  id_event integer REFERENCES galette_events_events (id_event) ON DELETE CASCADE ON UPDATE CASCADE,
  id_adh integer REFERENCES galette_adherents (id_adh) ON DELETE CASCADE ON UPDATE CASCADE,
  booking_date date default '19010101' NOT NULL,
  is_paid boolean default FALSE,
  payment_amount real default '0',
  payment_method smallint default '0' NOT NULL,
  bank_name character varying(100) default NULL,
  check_number character varying(50) default NULL,
  number_people smallint default NULL,
  creation_date date default '19010101' NOT NULL,
  comment text,
  PRIMARY KEY (id_booking),
  UNIQUE (id_event, id_adh)
);

--
-- Table structure for table `galette_events_activities`
--

DROP SEQUENCE IF EXISTS galette_events_activities_id_seq;
CREATE SEQUENCE galette_events_activities_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_events_activities CASCADE;
CREATE TABLE galette_events_activities (
  id_activity integer DEFAULT nextval('galette_events_activities_id_seq'::text) NOT NULL,
  name character varying(150) NOT NULL,
  is_active boolean default TRUE,
  creation_date date default '19010101' NOT NULL,
  comment text,
  PRIMARY KEY (id_activity)
);

DROP TABLE IF EXISTS galette_events_activitiesevents CASCADE;
CREATE TABLE galette_events_activitiesevents (
  id_event integer REFERENCES galette_events_events (id_event) ON DELETE CASCADE ON UPDATE CASCADE,
  id_activity integer REFERENCES galette_events_activities (id_activity) ON DELETE CASCADE ON UPDATE CASCADE,
  status smallint NOT NULL,
  PRIMARY KEY(id_event,id_activity)
);

DROP SEQUENCE IF EXISTS galette_events_activitiesbookings_id_seq;
CREATE SEQUENCE galette_events_activitiesbookings_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

DROP TABLE IF EXISTS galette_events_activitiesbookings CASCADE;
CREATE TABLE galette_events_activitiesbookings (
  id_activitybooking integer DEFAULT nextval('galette_events_activitiesbookings_id_seq'::text) NOT NULL,
  id_activity integer REFERENCES galette_events_activities (id_activity) ON DELETE CASCADE ON UPDATE CASCADE,
  id_booking integer REFERENCES galette_events_bookings (id_booking) ON DELETE CASCADE ON UPDATE CASCADE,
  checked boolean default FALSE,
  PRIMARY KEY (id_activitybooking),
  UNIQUE (id_activity, id_booking)
);
