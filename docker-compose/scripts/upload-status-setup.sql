-- ----------------------------
--  Sequence structure for cart_items_id_seq
-- ----------------------------
CREATE SEQUENCE "cart_items_id_seq" INCREMENT 1 START 1 MAXVALUE 9223372036854775807 MINVALUE 1 CACHE 1;
ALTER TABLE "cart_items_id_seq" OWNER TO "pacifica";

-- ----------------------------
--  Table structure for cart_items
-- ----------------------------
CREATE TABLE "cart_items" (
	"id" int8 NOT NULL DEFAULT nextval('cart_items_id_seq'::regclass),
	"file_id" int8 NOT NULL,
	"cart_uuid" varchar(64) NOT NULL COLLATE "default",
	"relative_local_path" varchar NOT NULL COLLATE "default",
	"file_size_bytes" int8 NOT NULL,
	"file_mime_type" varchar COLLATE "default"
)
WITH (OIDS=FALSE);
ALTER TABLE "cart_items" OWNER TO "pacifica";

-- ----------------------------
--  Table structure for cart
-- ----------------------------
CREATE TABLE "cart" (
	"cart_uuid" varchar(64) NOT NULL COLLATE "default",
	"name" varchar COLLATE "default",
	"description" varchar COLLATE "default",
	"owner" int4 NOT NULL,
	"json_submission" json NOT NULL,
	"created" timestamp(6) NOT NULL DEFAULT now(),
	"updated" timestamp(6) NOT NULL,
	"deleted" timestamp(6) NULL
)
WITH (OIDS=FALSE);
ALTER TABLE "cart" OWNER TO "pacifica";

-- ----------------------------
--  Table structure for cart_download_stats
-- ----------------------------
CREATE TABLE "cart_download_stats" (
	"cart_uuid" varchar(64) NOT NULL COLLATE "default",
	"downloader_id" int4 NOT NULL,
	"downloader_ip_addr" inet NOT NULL,
	"download_start_time" timestamp(6) NOT NULL DEFAULT now(),
	"download_completion_time" timestamp(6) NULL
)
WITH (OIDS=FALSE);
ALTER TABLE "cart_download_stats" OWNER TO "pacifica";


-- ----------------------------
--  Alter sequences owned by
-- ----------------------------
ALTER SEQUENCE "cart_items_id_seq" RESTART 2 OWNED BY "cart_items"."id";
-- ----------------------------
--  Primary key structure for table cart_items
-- ----------------------------
ALTER TABLE "cart_items" ADD PRIMARY KEY ("id") NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ----------------------------
--  Primary key structure for table cart
-- ----------------------------
ALTER TABLE "cart" ADD PRIMARY KEY ("cart_uuid") NOT DEFERRABLE INITIALLY IMMEDIATE;
