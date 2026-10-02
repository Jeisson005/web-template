export interface FtpConfig {
  host: string;
  user: string;
  password?: string;
  port?: number;
  secure?: boolean | 'implicit';
  remoteDir?: string;
  publicUrl?: string;
}

export interface FtpStorageOptions {
  /**
   * Collections that should use FTP storage (e.g. { media: true })
   */
  collections: Record<string, boolean | { prefix?: string }>;
  /**
   * FTP connection configuration
   */
  config: FtpConfig;
  /**
   * Whether the plugin is enabled
   */
  enabled?: boolean;
}
