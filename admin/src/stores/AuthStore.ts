import { defineStore } from 'pinia';
import { LocalStorage } from 'quasar';
import { api } from 'boot/app';
import { UserType } from 'src/api/Enum/UserType';
import { UserResource } from 'src/api/Resource/UserResource';
import { watch } from 'vue';
import { Permission } from 'src/api/Enum/Permission';
import { AxiosError } from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: LocalStorage.getItem('user') as UserResource,
    appToken: LocalStorage.getItem('appToken'),
    refreshToken: LocalStorage.getItem('refreshToken'),
    switchedUser: LocalStorage.getItem('switchedUser'),
    isRefreshingState: false,
    isLogoutState: false,
  }),

  actions: {
    /**
     * Login with Username & Password
     */
    async loginUsername(username: string, password: string) {
      this.clearToken();

      await api
        .securityLogin({ username: username, password: password })
        .then((r) => {
          this.user = r.data.data;
          this.appToken = r.data.token;
          this.refreshToken = r.data.refresh_token;
          if (this.user.language) {
            localStorage.setItem('user_locale', this.user.language);
          }

          // Redirect
          this.router.push({ path: '/' });
        })
        .catch((r: AxiosError) => {
          if (r.response?.data?.type === 'AccountNotActivatedException') {
            this.loginOtpRequest(username);
          }
        });
    },

    /**
     * Login with Passwordless Request
     */
    async loginOtpRequest(username: string) {
      await api.securityLoginOtpRequest({ username: username }).then(() => {
        this.router.push({
          name: 'auth.login.otp',
          params: { id: btoa(username) },
        });
      });
    },

    /**
     * Login with Passwordless
     */
    async loginOtp(username: string, otpKey: number) {
      this.clearToken();

      await api.securityLoginOtp({ username: username, otp_key: otpKey }).then((r) => {
        this.user = r.data.data;
        this.appToken = r.data.token;
        this.refreshToken = r.data.refresh_token;
        if (this.user.language) {
          localStorage.setItem('user_locale', this.user.language);
        }

        // Redirect
        this.router.push({ path: '/' });
      });
    },

    /**
     * Reload token with Refresh Token
     */
    reloadTokenWithRefreshToken(): Promise<any> {
      if (this.isRefreshingState) {
        return this.isRefreshingState;
      }

      return (this.isRefreshingState = api
        .securityRefreshToken({ refresh_token: this.refreshToken }, { skipInterceptor: true })
        .then((r) => (this.appToken = r.data.data.token))
        .finally(() => (this.isRefreshingState = false)));
    },

    /**
     * Reload User in Profile Api
     */
    async reloadUser() {
      if (this.isLoggedIn()) {
        await api.accountShowProfile().then((r) => {
          this.updateUser(r.data.data);
        });
      }
    },

    /**
     * Update User State
     */
    updateUser(user: UserResource) {
      this.user = user;
    },

    /**
     * Logout User and Clear Token
     */
    async logout(showMessage = false) {
      this.router.push({ name: 'auth.login' }).then(() => {
        this.clearToken();

        if (!this.isLogoutState) {
          this.isLogoutState = api
            .securityLogout({ refresh_token: this.refreshToken }, { showMessage: showMessage })
            .finally(() => (this.isLogoutState = false));
        }
      });
    },

    /**
     * Switch User
     */
    async switchUser(username: string) {
      await api.accountShowProfile({ headers: { SWITCH_USER: username } }).then((r) => {
        if (r.data.data.type !== UserType.USER) {
          this.switchedUser = username;
          this.updateUser(r.data.data);
          this.router.push({ path: '/' }).then(() => {
            window.location.reload();
          });
        }
      });
    },

    /**
     * Logout Switch User
     */
    async switchUserLogout(redirect: boolean) {
      this.switchedUser = null;
      await this.reloadUser();
      if (redirect) {
        this.router.push({ path: '/' }).then(() => {
          window.location.reload();
        });
      }
    },

    /**
     * Check Switched User
     */
    isSwitchedUser(): boolean {
      return this.switchedUser !== null;
    },

    /**
     * Clear Token
     */
    clearToken() {
      this.user = null;
      this.appToken = null;
      this.refreshToken = null;
      this.switchedUser = null;
    },

    /**
     * Check Login
     */
    isLoggedIn(): boolean {
      return this.appToken && this.user;
    },

    /**
     * Check User Type
     */
    hasUserType(userType: UserType | Array<string>): boolean {
      if (Array.isArray(userType)) {
        return userType.includes(this.user.type);
      }

      return userType === this.user.type;
    },

    /**
     * Check User Permission
     */
    hasPermission(permission: string | Array<string>, user?: UserResource): boolean {
      // Super Admin
      if ((user || this.user).type === UserType.SUPERADMIN) {
        return true;
      }

      if (Array.isArray(permission)) {
        return permission.every((r) => (user || this.user).roles.indexOf(r) !== -1);
      }

      return (user || this.user).roles.includes(permission);
    },

    /**
     * Get Readable Permissions
     */
    getReadablePermission(permissions: typeof Permission, user?: UserResource) {
      const perms = {};

      Object.entries(permissions).forEach(([type, permList]: [string, any]) => {
        perms[type] = {};
        Object.entries(permList).forEach(([key, perm]: [string, any]) => {
          if (this.hasPermission(perm, user || this.user)) {
            perms[type][key] = perm;
          }
        });

        // Clear Empty Group
        if (Object.values(perms[type]).length === 0) {
          delete perms[type];
        }
      });

      return perms;
    },
  },
});

// Set State to LocalStorage
watch(useAuthStore().$state, (states) => {
  Object.entries(states).forEach(([k, v]) => {
    if (['isLogoutState', 'isRefreshingState'].includes(k)) {
      return;
    }

    if (v === null) {
      LocalStorage.remove(k);
    } else {
      LocalStorage.set(k, v);
    }
  });
});
