export const IndexBreadCrumb = {
  manage:{
    title: '基础管理',
    path:[]
  },
  setting: {
    title: '镜像仓库管理',
    path:['manage']
  },
  account: {
    title: '账户管理',
    path:['manage']
  },
  projectList: {
    title: '项目管理',
    path:[]
  },
  projectCreate: {
    title: '创建项目',
    path: ['projectList']
  },
  projectEdit: {
    title: '编辑项目',
    path: ['projectList']
  },
  topology:{
    title: '业务拓扑定义',
    path: []
  },
  compose: {
    title: '编排模版',
    path: []
  },
  instance: {
    title: '实例管理',
    path: []
  },
  env: {
    title: '容器配置管理',
    path: ['instance']
  },
  container: {
    title: '容器管理',
    path: []
  },
  script: {
    title: '命令管理',
    path: []
  },
  scriptCreate: {
    title: '创建命令',
    path: ['script']
  },
  scriptEdit: {
    title: '修改命令',
    path: ['script']
  },
  customerGroup: {
    title: '自定义组',
    path: []
  },
  createInstanceGroup: {
    title: '创建自定义实例组',
    path: ['customerGroup']
  },
  createCustomerGroup: {
    title: '创建自定义组',
    path: ['customerGroup']
  },
  editCustomerGroup: {
    title: '修改自定义组',
    path: ['customerGroup']
  },
  task: {
    title: '任务管理',
    path: []
  },
  taskCreate: {
    title: '创建任务',
    path: ['task']
  },
  taskEdit: {
    title: '修改任务',
    path: ['task']
  },
  cronJob: {
    title: '定时任务管理',
    path: []
  },
  cronJobCreate: {
    title: '创建定时任务',
    path: ['cronJob']
  },
  cronJobEdit: {
    title: '修改定时任务',
    path: ['cronJob']
  },
  log: {
    title: '日志管理',
    path: []
  },
  logDetail: {
    title: '日志详情',
    path: ['log']
  },
  perm: {
    title: '项目授权',
    path: []
  }
}
export const ManageBreadCrumb = {
  manageCreate:''
}
export const ProjectBreadCrumb = {
  projectCreate:'创建项目'
}

export class BreadCrumbModule{
  link:string;
  title:string;
}
