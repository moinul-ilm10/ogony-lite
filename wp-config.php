<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'practice' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '10X6kbPB!*yIglSZergewPbpZDvG@jr6J)ayP=u3%=uXY-z([1}mpy+5Ss+fWaIV' );
define( 'SECURE_AUTH_KEY',  'htH@wu=np^V.(D)=_dnMD`5i^$Vu;8Kciz DIej^]G0h{u&1Suub@ns;.w-T~ ~6' );
define( 'LOGGED_IN_KEY',    'U#y;jAd+Ggjr}AwNrpK*o(l@AB-`0rqhw~[92lUTN0X>K)GIR/53bo0Pjn;gD5lN' );
define( 'NONCE_KEY',        'u4Q /b<i~=WImdb]Y0dT]nD87z.aANOZe=*=bc0|pZ^hgXUi7ar`R1SH!Rx`]uEH' );
define( 'AUTH_SALT',        'I+vZ]:_FVYmGH_$O^N08.}uKDf6C1;4U#A@6xe&xK=ID;#>9`JSHvZ91fhw9~0LC' );
define( 'SECURE_AUTH_SALT', 'l7En(sVe7M`hrIU&nL;U[kZWx<SB+Vr#;`s#eMxjh[wv7fR*g/f*s0mW6dRuUS:n' );
define( 'LOGGED_IN_SALT',   '9j#&hQb.`B0iMWFFN$XT+~/4R#uzr62&m 88DxnC.of.9pdEy?SWt,N%s{{4;PWV' );
define( 'NONCE_SALT',       ' z>JUKY^sUNFCo&VDzW4u!a`IW&8.,K9 _:(L-.v0*CX9zG3ssX#aU,Gk=nM&igx' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
