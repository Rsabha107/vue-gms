// Ziggy route helper — injected globally by ZiggyVue plugin
declare function route(name: string, params?: object | undefined, absolute?: boolean): string
declare function route(): { current: (name?: string) => boolean }

declare module "vue-filepond/dist/vue-filepond.esm.js" {
  import { DefineComponent } from "vue";
  export function setOptions(options: object): void;
  const vueFilePond: (...plugins: any[]) => DefineComponent<any, any, any>;
  export default vueFilePond;
}
