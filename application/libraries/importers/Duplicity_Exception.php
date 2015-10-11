<?php
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
 * Duplicity exception is thrown during bank statement import when
 * some transfers are already imported in database.
 *
 * @author Jiri Svitak
 */
class Duplicity_Exception extends Exception {}
