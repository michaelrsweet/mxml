--
-- "$Id: mxml.sql,v 1.7 2004/05/21 03:59:17 mike Exp $"
--
-- Database schema for the Mini-XML web pages.
--
-- This SQL file is specifically for use with the SQLite database
-- engine, but is probably portable to other databases like MySQL
-- and Postgresql.
--
-- Revision History:
--
--   M. Sweet   05/17/2004   Initial revision.
--   M. Sweet   05/19/2004   Added link, poll, and vote tables.
--   M. Sweet   05/20/2004   Changes for MySQL


--
-- Schema for table 'article'
--
-- This table lists the available articles for each application.
-- Articles correspond roughly to FAQs, HOWTOs, and news announcements,
-- and they can be searched.
--

CREATE TABLE article (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Article number
  is_published INTEGER,			-- 0 = private, 1 = public
  title VARCHAR(255),			-- Title of article
  abstract VARCHAR(255),		-- Plain text abstract of article
  contents TEXT,			-- Contents of article
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255),		-- User that created the article
  modify_date INTEGER,			-- Time/date of last change
  modify_user VARCHAR(255)		-- User that made the last change
);


--
-- Schema for table 'carboncopy'
--
-- This table tracks users that want to be notified when a resource is
-- modified.  Resources are tracked by filename/URL...
--
-- This is used to notify users whenever a STR or article is updated.
--

CREATE TABLE carboncopy (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Carbon copy ID
  url VARCHAR(255),			-- File or URL
  email VARCHAR(255)			-- Email address
);


--
-- Schema for table 'comment'
--
-- This table tracks comments that are added to a page on the web site.
-- Comments are associated with a specific URL, so you can make comments
-- on any page on the site...
--

CREATE TABLE comment (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Comment ID number
  parent_id INTEGER,			-- Parent comment ID number (reply-to)
  status INTEGER,			-- Moderation status, 0 = dead to 5 = great
  url VARCHAR(255),			-- File/link this comment applies to
  contents text,			-- Comment message
  create_date INTEGER,			-- Date the comment was posted
  create_user VARCHAR(255)		-- Author name/email
);


--
-- Table structure for table 'link'
--
-- This table lists links to external applications, web pages, etc.
-- Basically, we end up providing a hierachical, searchable link list,
-- complete with comments from users...
--

CREATE TABLE link (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Link ID number
  parent_id INTEGER,			-- Parent link ID or 0 for top-level
  is_category INTEGER,			-- 0 = listing, 1 = category
  is_published INTEGER,			-- 0 = private, 1 = public
  name VARCHAR(255),			-- Link name
  version VARCHAR(255),			-- Current version number string
  license VARCHAR(255),			-- Current license
  author VARCHAR(255),			-- Current author
  email VARCHAR(255),			-- Public email address
  homepage_url VARCHAR(255),		-- Home page
  download_url VARCHAR(255),		-- Download page
  description TEXT,			-- Description of link
  rating_total INTEGER,			-- Total of all ratings
  rating_count INTEGER,			-- Number of ratings
  homepage_visits INTEGER,		-- Number of clicks to the home page
  download_visits INTEGER,		-- Number of clicks to the download page
  create_date INTEGER,			-- Creation time/date
  create_user VARCHAR(255),		-- User that created the link
  modify_date INTEGER,			-- Last time/date changed
  modify_user VARCHAR(255)		-- User that made the last change
);


--
-- Table structure for table 'poll'
--
-- This table provides a very simple single question, multiple choice poll
-- interface for the main page.  Used successfully for a couple years now
-- on the CUPS and FLTK sites, the main twist is the new poll_type field
-- to control whether it is pick-one or pick-many poll.
--

CREATE TABLE poll (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Poll ID number
  is_published INTEGER,			-- 0 = private, 1 = public
  poll_type INTEGER,			-- 0 = pick one, 1 = pick many
  question VARCHAR(255),		-- Question plain text
  answer0 VARCHAR(255),			-- Answer #1 plain text
  count0 INTEGER,			-- Number of votes for #1
  answer1 VARCHAR(255),			-- Answer #2 plain text
  count1 INTEGER,			-- Number of votes for #2
  answer2 VARCHAR(255),			-- Answer #3 plain text
  count2 INTEGER,			-- Number of votes for #3
  answer3 VARCHAR(255),			-- Answer #4 plain text
  count3 INTEGER,			-- Number of votes for #4
  answer4 VARCHAR(255),			-- Answer #5 plain text
  count4 INTEGER,			-- Number of votes for #5
  answer5 VARCHAR(255),			-- Answer #6 plain text
  count5 INTEGER,			-- Number of votes for #6
  answer6 VARCHAR(255),			-- Answer #7 plain text
  count6 INTEGER,			-- Number of votes for #7
  answer7 VARCHAR(255),			-- Answer #8 plain text
  count7 INTEGER,			-- Number of votes for #8
  answer8 VARCHAR(255),			-- Answer #9 plain text
  count8 INTEGER,			-- Number of votes for #9
  answer9 VARCHAR(255),			-- Answer #10 plain text
  count9 INTEGER,			-- Number of votes for #10
  votes INTEGER,			-- Total votes
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255),		-- User that created the poll
  modify_date INTEGER,			-- Time/date of last change
  modify_user VARCHAR(255)		-- User that made the last change
);


--
-- Schema for table 'str'
--
-- This table stores software trouble reports.
--

CREATE TABLE str (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- STR number
  master_id INTEGER,			-- "Duplicate of" number
  is_published INTEGER,			-- 0 = private, 1 = public
  status INTEGER,			-- 1 = closed/resolved,
					-- 2 = closed/unresolved,
					-- 3 = active, 4 = pending, 5 = new
  priority INTEGER,			-- 1 = rfe, 2 = low, 3 = moderate,
					-- 4 = high, 5 = critical
  scope INTEGER,			-- 1 = unit, 2 = function, 3 = software
  summary text,				-- Plain text summary
  subsystem VARCHAR(255),		-- Subsystem name
  str_version VARCHAR(16),		-- Software version for STR
  fix_version VARCHAR(16),		-- Software version for fix
  manager_email VARCHAR(255),		-- Manager of STR
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255),		-- User that created the STR
  modify_date INTEGER,			-- Time/date of last change
  modify_user VARCHAR(255)		-- User that made the last change
);


--
-- Schema for table 'strfile'
--
-- This table tracks the files that are attached to a STR.
--

CREATE TABLE strfile (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- File ID
  str_id INTEGER,			-- STR number
  is_published INTEGER,			-- 0 = private, 1 = public
  filename VARCHAR(255),		-- Name of file
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255)		-- User that posted the file
);


--
-- Schema for table 'strtext'
--
-- This table tracks the text messages that are attached to a STR.
--

CREATE TABLE strtext (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- Text ID
  str_id INTEGER,			-- STR number
  is_published INTEGER,			-- 0 = private, 1 = public
  contents TEXT,			-- Text message
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255)		-- User that posted the text
);


--
-- Schema for table 'users'
--
-- This table lists the users that work on Mini-XML.  Various pages use
-- this table when doing login/logout stuff and when listing the available
-- users to assign stuff to.
--

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTO_INCREMENT,-- ID
  is_published INTEGER,			-- 0 = private, 1 = public
  name VARCHAR(255),			-- Login name
  email VARCHAR(255),			-- Name/email address
  hash CHAR(32),			-- MD5 hash of name:password
  level INTEGER,			-- 0 = normal user, 100 = admin user
  create_date INTEGER,			-- Time/date of creation
  create_user VARCHAR(255),		-- User that created the user
  modify_date INTEGER,			-- Time/date of last change
  modify_user VARCHAR(255)		-- User that made the last change
);


--
-- Table structure for table 'vote'
--
-- This table is used to track ratings, poll votes, etc. that are made on
-- the links and poll pages.
--

CREATE TABLE vote (
  type_id_ip VARCHAR(255) PRIMARY KEY	-- type_id_ip
);

--
-- End of "$Id: mxml.sql,v 1.7 2004/05/21 03:59:17 mike Exp $".
--
