/* eslint-disable max-len */

import { UserResource } from '../Resource/UserResource';

export interface SecurityLoginOtpResponse {
  data: UserResource,
  token: string,
  refresh_token: string
}
