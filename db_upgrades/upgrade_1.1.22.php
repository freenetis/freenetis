<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 *
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 * More info about project can be found:
 * http://www.freenetis.org/
 *
 */

/**
 * Alters foreign keys in order to allow member delete. Names of foreign keys is
 * not fixed (auto-creation) thus for some deployments this can cause some
 * failures.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_sql['1.1.22'] = array
(
	"ALTER TABLE watchers DROP FOREIGN KEY watchers_ibfk_1;",
	"ALTER TABLE watchers ADD CONSTRAINT watchers_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;",

	"ALTER TABLE jobs DROP FOREIGN KEY jobs_ibfk_2;",
	"ALTER TABLE jobs ADD CONSTRAINT jobs_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;",

	"ALTER TABLE job_reports DROP FOREIGN KEY job_reports_ibfk_1;",
	"ALTER TABLE job_reports ADD CONSTRAINT job_reports_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;",

	"ALTER TABLE requests DROP FOREIGN KEY requests_ibfk_1;",
	"ALTER TABLE requests ADD CONSTRAINT requests_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;",
);
