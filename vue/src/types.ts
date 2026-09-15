/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

export interface Message {
  role: string;
  content: string;
}

export interface StreamChoice {
  delta?: { role?: string; content?: string };
  message?: { role?: string; content?: string };
}

export interface ApiResponse {
  choices?: StreamChoice[];
  error?: { message: string };
}
